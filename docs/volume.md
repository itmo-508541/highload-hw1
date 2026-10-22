# Docker volume (операционный runbook)

Именованный bind-том для постоянного файлового хранилища (локально и prod).
Пошаговый чеклист правок compose/CI для агента — скилл
[`add-docker-volume`](../.claude/skills/add-docker-volume/SKILL.md),
вызывается явно.
Соглашения `ENV_*` — также [`dotenv.md`](dotenv.md).

## Когда нужен том

В **`docker-compose.yml`** у **`php`** и **`nginx`** включён
**`read_only: true`** (в **`docker-compose.deploy.yml`** — также **`spare`**).
Корень ФС контейнера запечатан: запись только в **`tmpfs`** (например
**`/tmp`**) или в смонтированный **volume** / bind mount.

Постоянное состояние приложения (загрузки, выгрузки, отчёты) добавляют как
именованный bind-volume — одним набором правок для local и production.

Тома **`database`** / **`redis-data`** — другая модель персистентности; этот
runbook про bind на каталог хоста.

## Соглашения (кратко)

| Сущность | Пример | Где |
|----------|--------|-----|
| Имя тома | `uploads` | compose `volumes:` |
| Путь в контейнере | `/var/local/uploads` | `ENV_uploads_storage` в `.env` |
| Путь на хосте prod | `/var/local/uploads-storage` | CI `UPLOADS_STORAGE` (**без** `ENV_`) |

- Том — **kebab-case**; ENV в контейнере — **`ENV_<snake>_storage`**.
- Хостовый путь в GitLab — **SCREAMING_SNAKE** без префикса `ENV_` (иначе
  попадёт в `.env.prod.local` / prod-дамп).
- `device:` — только **абсолютный** путь; каталог на хосте должен существовать
  **до** `up`.

Полный список файлов к правке — в skill `add-docker-volume`.

## Добавление тома

1. Выполнить шаги skill `add-docker-volume` (или поручить агенту — промпт ниже).
2. Пройти smoke (ниже) локально; после первого деплоя — на сервере.
3. При новой CI-переменной хоста — пометка в [`DEPLOYMENT.md`](../DEPLOYMENT.md)
   (предусловия mainline), если перечень в тексте устарел.

Промпт для агента Cursor (подставьте имя тома):

```
Создай новый volume по skill add-docker-volume. Пусть волюм называется results
```

## Smoke

Пример для тома `uploads` → `/var/local/uploads` (замените путь):

```bash
make up
docker compose exec php sh -lc 'id; ls -la /var/local/uploads && touch /var/local/uploads/.smoke && rm /var/local/uploads/.smoke'
```

Ожидание: `touch`/`rm` без `Permission denied`; файл появляется на хосте по
пути из `device:`.

Проверка, что том не «выпал» из deploy-compose:

```bash
docker compose -f docker-compose.deploy.yml config
```

В выводе том должен быть и в верхнем `volumes:`, и в `services.*.volumes`.

Prod-каталог (до первого деплоя), PHP от `www-data` (обычно UID/GID **33**):

```bash
sudo mkdir -p /var/local/uploads-storage
sudo chown 33:33 /var/local/uploads-storage
sudo chmod 0700 /var/local/uploads-storage
```

Минимум `0700` (нужен execute для traverse).

## Подводные камни

- Том нигде не смонтирован → `compose config` выбросит его.
- Относительный `device:` → драйвер bind не примет.
- Каталог на хосте не создан → `up` падает.
- Каталог без execute (`0600`) → нельзя зайти внутрь.
- Хостовый путь с префиксом `ENV_` → утечёт в prod-дамп приложения.
- Compose интерполирует `${…}` из проектного `.env`, не из `.env.local` через
  `env_file`.

## Связанные

- Агентский чеклист правок — skill `add-docker-volume`
- Env / `ENV_*` — [`dotenv.md`](dotenv.md)
- Деплой / `REQUIRED_VARS` — [`DEPLOYMENT.md`](../DEPLOYMENT.md)
