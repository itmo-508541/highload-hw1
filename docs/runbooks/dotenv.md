# Dotenv (операционный runbook)

Как загружается окружение локально, в CI и на prod; ключевые группы переменных.
Совпадение env с Phing — в [`phing.md`](phing.md). Обзор деплоя —
[`DEPLOYMENT.md`](../../DEPLOYMENT.md).

## Локально и в CI на не-mainline ветках

Порядок загрузки env в Symfony (цепочка Dotenv / Runtime):

- `.env` (базовые значения для проекта, в репозитории);
- `.env.local` (локальные переопределения, не коммитятся);
- `.env.$APP_ENV` и `.env.$APP_ENV.local` (по окружениям).

В ветках **кроме** `master`/`production` пайплайн и разработчик опираются на
закоммиченный **`.env`** (плюс при необходимости локальные `.env.local` /
`.env.local.php` у разработчика).

## Production (mainline): дамп и Docker secrets

Для деплоя на **`master`/`production`** секреты и prod-переменные приходят из
GitLab CI: job **`prepare-mainline:env`** формирует артефакт
**`.env.prod.local`**, job **`deploy-mainline:prepare-secrets`** выполняет
**`composer symfony:dump-env prod`** и кладёт результат в
**`build/secrets/.env.local.php`**, который на сервере монтируется как Docker
secret **`/run/secrets/.env.local.php`**.

Symfony ожидает файл **`.env.local.php`** в корне приложения. В образе для prod
в [`docker-compose.deploy.yml`](../../docker-compose.deploy.yml) используется
**config**: файл [`secrets/env.php`](../../secrets/env.php) попадает в контейнер как
**`/srv/.env.local.php`** и делает
`return include '/run/secrets/.env.local.php';` — Runtime загружает обычный путь,
а массив окружения читается из секрета. Ключи JWT — отдельные secret-файлы
(`jwt_private.pem`, `jwt_public.pem`), пути к ним задаются в дампе (конфигурация
Lexik JWT).

Шаги CI и копирование на сервер — [`DEPLOYMENT.md`](../../DEPLOYMENT.md).

## Ключевые группы переменных

- БД: `ENV_database_host`, `ENV_database_port`, `ENV_database_name`,
  `ENV_database_user`, `ENV_database_password`, `ENV_database_root_password`.
- Redis: `ENV_redis_host`, `ENV_redis_port`, `ENV_redis_password`. Подключение в
  сервисах — через **`REDIS_URL`** (см. [`config/services.yaml`](../../config/services.yaml):
  `RedisAdapter::createConnection` с
  **`%env(resolve:REDIS_URL)%`**). URL и дефолты — в **`.env`** (как в рецептах
  Symfony Flex); дублирующей «сборки» URL в YAML нет. В **`qa:behat`** Dotenv
  грузит в основном **`.env`** — значения должны быть согласованы с сервисом
  Redis в CI.
- Приложение: `ENV_app_secret`.

Именованные bind-тома: путь **внутри** контейнера — `ENV_<name>_storage` в
`.env`; путь **на хосте** prod — переменная **без** префикса `ENV_` (иначе
попадёт в `.env.prod.local`). Runbook — [`volume.md`](volume.md); чеклист
правок — skill `add-docker-volume`.

## Важно

- Секреты production **не** хранятся в репозитории; значения — в **GitLab CI/CD
  Variables** (и File variables для ключей) → **`.env.prod.local`** → дамп
  **`/run/secrets/.env.local.php`** (см. [`DEPLOYMENT.md`](../../DEPLOYMENT.md)).
- Локально шаблонные значения из **`.env`** при необходимости переопределяют в
  **`.env.local`** (не коммитится).
- `ENV_redis_password`, `ENV_database_root_password` и остальные поля из
  **`REQUIRED_VARS`** валидируются в **`prepare-mainline:env`** — пайплайн
  упадёт на стадии `.pre`, если переменная не задана в GitLab.

## Связанные runbook'и

- Redis-сессии и smoke — [`redis.md`](redis.md)
- Phing / `symfonyenv` — [`phing.md`](phing.md)
- Bind-volume — [`volume.md`](volume.md)
