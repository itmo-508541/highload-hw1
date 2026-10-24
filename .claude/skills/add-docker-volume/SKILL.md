---
name: add-docker-volume
description: >-
  Добавляет именованный Docker volume с bind на каталог хоста для локальной
  разработки и production-деплоя (compose, .env, GitLab CI REQUIRED_VARS,
  права на сервере). Использовать когда нужен новый том/хранилище файлов,
  пользователь упоминает volume, uploads storage, bind-mount, ENV_*_storage,
  docker-compose.deploy.yml volumes, docs/runbooks/volume.md или skill add-docker-volume.
disable-model-invocation: true
---

# Добавление Docker volume (локально + prod)

Переиспользуемый рецепт: один именованный том одинаково устроен для локальной
разработки (`docker-compose.yml` + override) и production
(`docker-compose.deploy.yml` + GitLab CI/CD Variables).

Пример ниже — том **`uploads`**, путь в контейнере **`/var/local/uploads`**.
Перед применением замените имя и пути.

Этот skill — канонический **чеклист правок**. Операционный runbook (когда нужен
том, smoke, ловушки) — [`docs/runbooks/volume.md`](../../../docs/runbooks/volume.md).

## Соглашения по именам

| Сущность | Пример | Где |
|----------|--------|-----|
| Имя тома | `uploads` | `docker-compose.yml`, `docker-compose.deploy.yml` |
| Путь в контейнере | `/var/local/uploads` | через `ENV_uploads_storage` |
| ENV пути в контейнере | `ENV_uploads_storage` | `.env` |
| CI-переменная пути **на хосте** prod | `UPLOADS_STORAGE` | GitLab CI/CD Variables |

Правила:

- Имя тома — **kebab-case** (`uploads`, `report-cache`).
- ENV в контейнере — **`ENV_<snake>_storage`** (префикс `ENV_` обязателен: job
  `prepare-mainline:env` собирает все `ENV_*` в `.env.prod.local`).
- Хостовый путь в CI — **SCREAMING_SNAKE_CASE без** `ENV_` (`UPLOADS_STORAGE`).
  Префикс `ENV_*` для хостового пути запрещён — иначе путь попадёт в prod-дамп
  окружения приложения.
- Дефолт пути в контейнере: `/var/local/<имя-тома>`.

## Шаги (все в одном коммите)

### 1) `.env` — путь внутри контейнера

```dotenv
ENV_uploads_storage=/var/local/uploads
```

Compose интерполирует `${…}` из проектного `.env` (не из `.env.local`).

### 2) `docker-compose.yml`

В `services.php.volumes` добавить маунт (к существующим, не заменяя список):

```yaml
- uploads:${ENV_uploads_storage}
```

В верхнем `volumes:` — именованный bind с безопасным дефолтом `device`:

```yaml
uploads:
    driver: local
    driver_opts:
        type: none
        o: bind
        device: /tmp/uploads
```

`/tmp/uploads` — заглушка для `make up` после clone; для длительной работы
переопределите локально (шаг 4). Тома `database` / `redis-data` не трогать —
у них другая модель персистентности.

### 3) `docker-compose.deploy.yml`

В якоре `php: &php-service` добавить (или дополнить) `volumes:`:

```yaml
volumes:
    - uploads:${ENV_uploads_storage}
```

Том должен быть смонтирован хотя бы одним сервисом, иначе
`docker compose config` выбросит его из итогового файла.

В верхнем `volumes:`:

```yaml
uploads:
    driver: local
    driver_opts:
        type: none
        o: bind
        device: ${UPLOADS_STORAGE}
```

### 4) `docker-compose.override.yml.dist`

Добавить закомментированный шаблон локального `device:`:

```yaml
# volumes:
#     uploads:
#         driver_opts:
#             # абсолютный путь; каталог rw для пользователя разработчика
#             device: /tmp/uploads
```

При override достаточно `device:` — `type`/`o` мерджатся из базового compose.

### 5) `.gitlab-ci.yml`

В job `prepare-mainline:env` добавить `UPLOADS_STORAGE` в `REQUIRED_VARS`
(не в `REQUIRED_FILES`). Fail-fast на `.pre`, если переменная не задана.

### 6) GitLab → Settings → CI/CD → Variables

Создать `UPLOADS_STORAGE` для каждого Environment деплоя (минимум `master`,
и `production` при использовании). Значение — абсолютный путь к **существующему**
каталогу на сервере.

### 7) Каталог на prod-сервере (до первого деплоя)

PHP в prod работает от `www-data` (обычно UID/GID **33**):

```bash
sudo mkdir -p /var/local/uploads-storage
sudo chown 33:33 /var/local/uploads-storage
sudo chmod 0700 /var/local/uploads-storage
```

Минимум `0700` (нужен execute для traverse). Альтернатива: `0750`/`0770` с группой.

### 8) Каталог у разработчика

1. В локальном `docker-compose.override.yml` задать абсолютный `device:`.
2. `mkdir -p <путь>` (владелец = `user` сервиса `php`, обычно `$(id -u):$(id -g)`).
3. `make up`.

### 9) `DEPLOYMENT.md`

Если том — предусловие деплоя mainline, добавить о нём строку в раздел
предусловий: без каталога на сервере деплой упадёт, а узнать об этом
из compose нельзя.

## Проверка

Локально (и после деплоя — на сервере):

```bash
make up
docker compose exec php sh -lc 'id; ls -la /var/local/uploads && touch /var/local/uploads/.smoke && rm /var/local/uploads/.smoke'
```

Файл после `touch` должен появиться на хосте по пути из `device:`.
При `Permission denied` — права/владелец каталога (шаг 7/8).

Дополнительно для deploy-файла: `docker compose -f docker-compose.deploy.yml config`
должен показать том в верхнем `volumes:` и в `services.*.volumes`.

## Чеклист

- [ ] `docker compose -f docker-compose.deploy.yml config` показывает том
      и в верхнем `volumes:`, и в `services.*.volumes`
- [ ] Права каталога дают execute: минимум `0700` у владельца процесса PHP
- [ ] Каталог создан на **каждом** prod-сервере, переменная задана
      для **каждого** Environment деплоя
- [ ] Файл, созданный внутри контейнера, виден на хосте по пути из `device:`

## Подводные камни

- Том нигде не смонтирован → `compose config` выбросит его.
- Относительный `device:` → драйвер bind принимает только абсолютные пути.
- Каталог на хосте не существует → `up` падает.
- Каталог без execute (`0600`) → нельзя зайти внутрь; минимум `0700`.
- Хостовый путь с префиксом `ENV_` → попадёт в `.env.prod.local` / prod-дамп.
- Интерполяция Compose читает проектный `.env`, не `.env.local` через `env_file`.
