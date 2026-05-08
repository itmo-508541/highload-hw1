# Создание нового volume для локального окружения и production

Документ — **переиспользуемый рецепт**: как добавить в проект новый именованный том, который одинаково устроен и для локальной разработки (через `docker-compose.yml` + `docker-compose.override.yml`), и для production-деплоя (через `docker-compose.deploy.yml` + переменные GitLab CI/CD).

В инструкции в качестве примера используется условное имя тома **`uploads`** и каталог **`/var/local/uploads`** внутри контейнера. Перед применением замените на нужное имя/путь.

## Соглашения по именам

| Сущность                                | Пример               | Где живёт                                           |
|-----------------------------------------|----------------------|-----------------------------------------------------|
| Имя тома                                | `uploads`            | `docker-compose.yml`, `docker-compose.deploy.yml`   |
| Путь монтирования **внутри контейнера** | `/var/local/uploads` | задаётся через `ENV_uploads_storage`                |
| Переменная для пути в контейнере        | `ENV_uploads_storage`| [`.env`](../../.env)                                |
| Переменная для пути **на хосте prod**   | `UPLOADS_STORAGE`    | GitLab CI/CD Variables, см. [`.gitlab-ci.yml`](../../.gitlab-ci.yml) |

Соглашения, которым нужно следовать:

- Имя тома — **kebab-case** (`uploads`, `report-cache`, `media-store`).
- Имя ENV для пути в контейнере — **snake_case** с префиксом **`ENV_`** и суффиксом **`_storage`**: `ENV_uploads_storage`, `ENV_report_cache_storage`. Префикс `ENV_*` важен, его читает шаблон **`.prepare-env`** из [`.gitlab-ci.yml`](../../.gitlab-ci.yml) и пробрасывает в `.env.local` контейнеров.
- Имя CI/CD-переменной для **хостового** пути — **SCREAMING_SNAKE_CASE** **без** префикса `ENV_`: `UPLOADS_STORAGE`. Префикс `ENV_*` тут запрещён — иначе хостовый путь окажется в `.env.local` приложения, а это путь хоста, не путь контейнера.
- По умолчанию путь монтирования в контейнере — `/var/local/<имя-тома>` (для нашего примера `/var/local/uploads`).

## Шаги

Все правки в коммите должны идти **вместе**: пропуск любого пункта ломает либо локальный запуск, либо CI-сборку, либо первый prod-деплой.

### 1) `.env` — путь внутри контейнера

В [`.env`](../../.env) добавить переменную с путём монтирования **внутри контейнера**:

```dotenv
ENV_uploads_storage=/var/local/uploads
```

Этот путь видят и Compose (для `${...}`-интерполяции), и приложение (если позже понадобится прочитать его в Symfony через `%env(...)%`).

### 2) `docker-compose.yml` — локальное объявление и монтирование

В [`docker-compose.yml`](../../docker-compose.yml) сделать **две** правки.

В сервисе `php` (или другом, где том нужен) — подключить именованный том:

```yaml
services:
    php:
        volumes:
            - .:/srv
            - uploads:${ENV_uploads_storage}
```

В верхнем разделе `volumes:` — добавить «именованный bind-mount» с безопасным дефолтом для `device`:

```yaml
volumes:
    database:
    uploads:
        driver: local
        driver_opts:
            type: none
            o: bind
            device: /tmp/uploads
```

Дефолт `/tmp/uploads` — заглушка, чтобы локальный `make up` работал «из коробки» сразу после `git clone`. Содержимое `/tmp` не переживёт перезагрузку хоста, поэтому на любой длительной работе путь надо переопределить локально (см. шаг 4).

### 3) `docker-compose.deploy.yml` — prod-объявление

В [`docker-compose.deploy.yml`](../../docker-compose.deploy.yml) — две симметричные правки.

В якоре `php: &php-service` (так маунт автоматически унаследуют все сервисы, объявленные через `<< : *php-service`, например `spare`):

```yaml
services:
    php: &php-service
        # ... остальные поля ...
        volumes:
            - uploads:${ENV_uploads_storage}
```

> Если у `&php-service` уже перечислены другие тома, добавьте `uploads:` к существующему списку — **не** заменяйте его. Том должен быть смонтирован **хотя бы одним** сервисом, иначе `docker compose config` выкинет его из итогового `build/docker-compose.yml`.

В верхнем разделе `volumes:` — описание тома с подстановкой пути на хосте из CI:

```yaml
volumes:
    database:
    uploads:
        driver: local
        driver_opts:
            type: none
            o: bind
            device: ${UPLOADS_STORAGE}
```

### 4) `docker-compose.override.yml.dist` — шаблон локальной настройки

В [`docker-compose.override.yml.dist`](../../docker-compose.override.yml.dist) добавить **закомментированный** блок (или дополнить уже существующий блок `volumes:` записью про новый том):

```yaml
# volumes:
#     uploads:
#         driver_opts:
#             # абсолютный путь (относительные не принимаются)
#             # каталог должен быть доступен на чтение и запись пользователю разработчика
#             device: /tmp/uploads
```

При локальном переопределении достаточно указать только `device:` — `type: none` и `o: bind` мерджатся из базового `docker-compose.yml`, потому что Compose сливает `driver_opts` ключ-в-ключ.

### 5) `.gitlab-ci.yml` — обязательная переменная для деплоя

В [`.gitlab-ci.yml`](../../.gitlab-ci.yml) добавить новую переменную в `REQUIRED_VARS` job `prepare-mainline:env`:

```yaml
prepare-mainline:env:
  extends:
    - .prepare-env
    - .job-policy-mainline
  variables:
    REQUIRED_VARS: SERVER_NAME DEPLOY_USER DEPLOY_HOST DEPLOY_DIRECTORY SSH_PRIVATE_KEY UPLOADS_STORAGE
```

Это даёт fail-fast: если переменная для окружения не задана в GitLab, пайплайн упадёт на стадии `.pre`, а не при `docker compose up` на сервере с пустым `device:`.

### 6) GitLab → Settings → CI/CD → Variables

Создать переменную `UPLOADS_STORAGE` **для каждого Environment**, на которое делается деплой (как минимум `master`, и `production` — если используется). Значение — абсолютный путь к **существующему** каталогу на хосте сервера развёртывания (например `/var/local/uploads-storage`). Если для какого-то окружения переменная не создана — `prepare-mainline:env` остановит пайплайн.

### 7) Каталог на prod-сервере

На каждом сервере развёртывания **до первого деплоя** вручную создать каталог по пути из `UPLOADS_STORAGE`. PHP-процессы в prod-образе работают от **`www-data`** (в Debian обычно **UID/GID 33**), поэтому:

```bash
sudo mkdir -p /var/local/uploads-storage
sudo chown 33:33 /var/local/uploads-storage
sudo chmod 0700 /var/local/uploads-storage
```

`0700` — минимально необходимое: бит **execute** на каталог даёт *traverse* (без него процесс не сможет открыть/создать файл внутри, даже имея `rw`). Если по политике безопасности каталог не должен принадлежать UID 33 эксклюзивно — использовать `0750`/`0770` с подходящей группой.

### 8) Каталог на машине разработчика

После `git pull` каждый разработчик (один раз):

1. В скопированном `docker-compose.override.yml` — раскомментировать/добавить блок `volumes.uploads.driver_opts.device` и подставить **абсолютный** путь к каталогу на хосте (удобно держать рядом с репозиторием, например `../<repo>-storage/uploads`).
2. Создать каталог: `mkdir -p <путь>`. Владелец/группа должны совпадать с UID/GID из поля `user` сервиса `php` (`docker-compose.override.yml`); для типичной настройки `user: "$(id -u):$(id -g)"` это уже текущий пользователь.
3. `make up` (или `docker compose up -d`).

## Проверка

### Локально

```bash
make up
docker compose exec php sh -lc 'id; ls -la /var/local/uploads && touch /var/local/uploads/.smoke && rm /var/local/uploads/.smoke'
```

В выводе должны быть:

- `id` процесса в контейнере соответствует UID/GID, заданным в `docker-compose.override.yml`;
- `ls -la` показывает каталог монтирования (пустой, `.` и `..`);
- `touch` создаёт файл, `rm` удаляет — оба без `Permission denied`.

После `touch` файл **должен** появиться на хосте по пути из `device:`.

### На сервере

После первого успешного деплоя на mainline (см. [`DEPLOYMENT.md`](../../DEPLOYMENT.md)):

```bash
docker compose exec php sh -lc 'id; ls -la /var/local/uploads && touch /var/local/uploads/.smoke && rm /var/local/uploads/.smoke'
```

Если smoke падает с `Permission denied` — проверять владельца/права каталога из шага 7.

## Чек-лист

- [ ] `.env`: `ENV_uploads_storage=/var/local/uploads`.
- [ ] `docker-compose.yml`: маунт в `services.php.volumes` и описание тома в верхнем `volumes:`.
- [ ] `docker-compose.deploy.yml`: маунт в якоре `&php-service` и описание тома с `device: ${UPLOADS_STORAGE}`.
- [ ] `docker-compose.override.yml.dist`: закомментированный шаблон `device:` для локалки.
- [ ] `.gitlab-ci.yml`: `UPLOADS_STORAGE` в `REQUIRED_VARS` `prepare-mainline:env`.
- [ ] GitLab Variables: `UPLOADS_STORAGE` создан для каждого нужного Environment.
- [ ] Каталог на каждом prod-сервере: создан, владелец `33:33`, права `0700` (или эквивалент).
- [ ] Локальный smoke-тест на запись в томе пройден.
- [ ] Запись в `CHANGELOG.md` под `[Unreleased]`.

## Подводные камни

- **Том нигде не смонтирован.** `docker compose config` (через который собирается `build/docker-compose.yml`) выкидывает неиспользуемые именованные тома. Если в `&php-service` забыли добавить маунт, на сервере `up` пройдёт без тома вовсе. Проверка локально: `docker compose -f docker-compose.deploy.yml config` должен показать том в верхнем `volumes:` и в `services.<name>.volumes`.
- **Относительный путь в `device:`.** Драйвер `local` с `o: bind` принимает только абсолютные пути.
- **Каталог не существует на хосте.** Драйвер не создаёт точку монтирования; `up` упадёт.
- **Каталог без бита execute.** `0600` запрещает заходить в каталог — внутрь нельзя ни прочитать, ни записать. Минимум `0700`.
- **Имя CI-переменной с префиксом `ENV_`.** Шаблон `.prepare-env` запишет хостовый путь в `.env.local` контейнера; путь хоста окажется доступен приложению как обычная переменная окружения, что почти всегда нежелательно.
- **`.env.local` для интерполяции `${...}` в Compose.** Compose читает `.env.local` **только** через `services.<name>.env_file` для прокидывания переменных **внутрь контейнера**; для интерполяции в самом compose-файле используется проектный `.env` (или флаг `--env-file`). Поэтому `ENV_uploads_storage` определяется именно в `.env`.

## Документирование нового тома

После применения этой инструкции по соглашениям из [`DOCUMENTATION.md`](../../DOCUMENTATION.md):

- если том — часть конкретного **этапа реализации** (`docs/NN-*.md`), краткое упоминание добавляется в раздел «Технические детали реализации» обзора этапа, а технический разбор шага живёт во вложенном файле каталога этапа (например `docs/<этап>/volume.md`) со ссылкой обратно на обзор;
- в [`DEPLOYMENT.md`](../../DEPLOYMENT.md), в раздел «Предусловия для mainline-деплоя», добавляется новая CI-переменная (`UPLOADS_STORAGE`) и пометка о подготовке каталога на сервере;
- в `CHANGELOG.md` под `[Unreleased]` фиксируется добавление тома (локально и на prod) со ссылками на соответствующие документы этапа.
