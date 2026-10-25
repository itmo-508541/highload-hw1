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
закоммиченный **`.env`** (плюс при необходимости локальный `.env.local`).

**`.env.local.php` в dev не создавать** — [ADR 0005](../adr/0005-env-dump-only-in-demo.md).
Если он появился, первым краснеет `tests/Integration/TestEnvironmentTest.php`.

В dev сервис `init` выключен (профиль `disabled`, команда `false`): инициализация —
`make init`.

## Demo-режим (prod-образ без override): дамп в томе

Почему устроено так — [ADR 0005](../adr/0005-env-dump-only-in-demo.md).

1. `init` стартует первым и собирает дамп:
   `tools/env-dump.php /run/dotenv-src /run/dotenv/.env.local.php` из `.env` →
   `.env.local` → `.env.prod` → `.env.prod.local` (оба `.local` — если есть).
   Без `.env` `init` падает.
2. Дамп лежит в именованном томе **`dotenv`** (`/run/dotenv`): у `init` на
   запись, у `php` только на чтение.
3. Шим [`secrets/env-demo.php`](../../secrets/env-demo.php) смонтирован в
   `php` и `init` как `/srv/.env.local.php` и подключает дамп из тома.
   `.env`-файлов в `php` нет.
4. `init` выполняет `phing`, `php` стартует только после успешного `init`.

Применить правку `.env*` — перезапустить demo (дамп руками не удалять):

```bash
docker compose down
```

```bash
docker compose up -d
```

Посмотреть значение в дампе:

```bash
docker compose exec php php -r 'var_export((include "/srv/.env.local.php")["ENV_default_uri"]);'
```

Ловушки:

- `docker compose up -d` поверх запущенного demo: при упавшем `init` уже
  работающий `php` остаётся на прежнем дампе. Перезапускать через `down`.
- `docker compose up php --no-deps` поднимет `php` с дампом прошлого старта.
- После обновления `docker-compose.override.yml.dist` скопируйте его заново:
  без `php.depends_on: !override` Compose отвергнет dev-проект
  (`service "php" depends on undefined service "init"`).
- Demo и dev собирают образ сервиса `php` под одним именем: при переключении
  режима пересоберите его (`docker compose build php`).

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
  Symfony Flex); дублирующей «сборки» URL в YAML нет. Исключение —
  **`.env.test`**: он переопределяет `REDIS_URL` целиком, той же сборкой
  из `ENV_redis_*`, но с индексом БД `/1` — записи `/items` из сценариев Behat
  не попадают в данные dev-окружения (см. [`redis.md`](redis.md)). В
  **`qa:behat`** Dotenv грузит в основном **`.env`** — значения должны быть
  согласованы с сервисом Redis в CI.
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
