# Symfony-приложение

Техническая детализация **как** устроены локальная разработка и сопутствующие команды в этом репозитории. Обзор **что** установлено и зачем нужен этап — в [`docs/00-symfony.md`](../00-symfony.md).

## Локальный цикл разработки

### 1) Первый запуск проекта

```bash
make up
make init
```

Что делает:

- `make up` поднимает контейнеры.
- `make init` запускает `phing` внутри `php`-контейнера, что приводит к:
  - подготовке БД и миграциям (`database-deploy`).

### 2) Установка/обновление зависимостей

Предпочтительно через Makefile (Composer выполняется в контейнере `php`, см. раздел выше):

```bash
make deps
```

Эквивалент без Makefile:

```bash
docker compose exec php composer install
```

### 3) Ежедневная разработка

- Основной рабочий путь: правки кода → локальная проверка → коммит.
- Для ручных запусков используйте `docker compose exec php ...`.

### 4) Обязательные проверки перед merge

Полный пакет:

```bash
make qa
```

Поштучно:

```bash
make cs
make phpstan
make rector
make test
make behat
```

### PHPUnit

Конфигурация: `phpunit.dist.xml`, bootstrap: `tests/bootstrap.php`. Запуск из корня репозитория (контейнер `php` должен быть запущен, см. `make up`):

```bash
# весь набор тестов
make test

# один файл или каталог (пути относительно корня проекта)
make test tests/EventListener/CorsRequestListenerTest.php

# то же через переменную ARGS (удобно, если не хотите передавать путь второй «целью» make)
make test ARGS="tests/EventListener/CorsRequestListenerTest.php"
```

Прямой вызов без Makefile:

```bash
docker compose exec php vendor/bin/phpunit
docker compose exec php vendor/bin/phpunit tests/EventListener/CorsRequestListenerTest.php
```

### Behat

Конфигурация: `behat.yml.dist`, bootstrap: `tests/bootstrap.php`. При необходимости локальных правок скопируйте `behat.yml.dist` в `behat.yml` (файл не коммитится). Цель `make behat` перед `vendor/bin/behat` запускает Phing с `-Dapp.env=test`, чтобы подготовить тестовое окружение и доступ к БД (как в CI). Запуск из корня репозитория (контейнер `php` должен быть запущен, см. `make up`):

```bash
# весь набор сценариев
make behat

# один feature-файл (пути относительно корня проекта)
make behat features/phing_dotenv_property_task.feature

# то же через переменную ARGS (удобно, если не хотите передавать путь второй «целью» make)
make behat ARGS="features/phing_dotenv_property_task.feature"
```

Прямой вызов без Makefile:

```bash
docker compose exec php vendor/bin/behat
docker compose exec php vendor/bin/behat features/phing_dotenv_property_task.feature
```

## Быстрые команды

```bash
make up
make init
make deps
make qa
```

## Хранилище сессий

Сессии Symfony хранятся в **Redis** через `Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler` (см. `config/packages/framework.yaml`). Сервис `\Redis` собирается фабрикой `Symfony\Component\Cache\Adapter\RedisAdapter::createConnection(%env(REDIS_URL)%)` (см. `config/services.yaml`). Префикс ключей в Redis — `sf_s:`.

В тестовом окружении (`when@test:` в `config/packages/framework.yaml`) `storage_factory_id` подменяется на `session.storage.factory.mock_file`, поэтому `make test` (PHPUnit) и `make behat` локально **не требуют** работающего Redis. В CI (`qa:behat`, см. [`.gitlab-ci.yml`](../../.gitlab-ci.yml)) Redis всё равно поднимается как сервис — ради согласованности с dev-стендом и совместимости с возможными будущими сценариями, которым понадобится реальное подключение.

Осмотр и smoke-проверка изнутри dev-Compose:

```bash
# список ключей сессий
docker compose exec redis redis-cli -a "$ENV_redis_password" --no-auth-warning KEYS 'sf_s:*'

# TTL конкретной сессии
docker compose exec redis redis-cli -a "$ENV_redis_password" --no-auth-warning TTL 'sf_s:<id>'
```

В `docker-compose.yml` сервису `redis` пробрасывается `REDISCLI_AUTH=${ENV_redis_password}`, поэтому `redis-cli` внутри контейнера авторизуется автоматически — `docker compose exec redis redis-cli PING` отвечает `PONG` даже без явного `-a`. Это означает, что сам по себе `PING` **не подтверждает**, что AUTH включён. Чтобы проверить факт включения пароля, надо явно передать заведомо неправильный пароль и убедиться, что сервер вернёт `WRONGPASS`:

```bash
docker compose exec redis redis-cli -a wrong_password --no-auth-warning PING
# → (error) WRONGPASS invalid username-password pair...
```

Persistence сессий между перезапусками обеспечивает именованный том `redis-data` (объявлен в `docker-compose.yml` и `docker-compose.deploy.yml`).

## Переменные окружения и конфигурация

Порядок загрузки env в Symfony:

- `.env` (базовые значения для проекта);
- `.env.local` (локальные переопределения, не коммитятся);
- `.env.$APP_ENV` и `.env.$APP_ENV.local` (по окружениям).

В Phing (`build.xml`) переменные окружения подгружает не сам Phing, а `Symfony\Component\Dotenv\Dotenv::bootEnv()` — её вызывает bootstrap из `<autoloader autoloaderpath="tools/phing-bootstrap.php" />`. Bootstrap читает `-Dapp.env=...` (по умолчанию `dev`) и `-Dapp.debug=...`, выставляет соответствующие `$_SERVER['APP_ENV']`/`$_SERVER['APP_DEBUG']` и вызывает `bootEnv` от корня репозитория — точно так же, как это делает Symfony Runtime для приложения. После загрузки в `SYMFONY_DOTENV_VARS` дописываются `APP_ENV`/`APP_DEBUG` — `bootEnv` сама их туда не добавляет, если они уже были в `$_ENV` до вызова.

Дальше Phing-задача `symfonyenv` (`tools/dotEnvPropertyTask.php`) копирует все имена из `$_SERVER['SYMFONY_DOTENV_VARS']` в свойства Phing с префиксом `dotenv` (значения берутся из `$_SERVER`, без `getenv`/`putenv`). Поэтому в `build.xml` обращения вида `${dotenv.ENV_database_host}` или `${dotenv.APP_ENV}` соответствуют тому, что приложение увидит через Symfony Runtime — включая случай, когда на проде есть `.env.local.php` (создаётся `composer dump-env prod` в CI).

Ключевые backend-группы переменных:

- БД: `ENV_database_host`, `ENV_database_port`, `ENV_database_name`, `ENV_database_user`, `ENV_database_password`, `ENV_database_root_password`.
- Redis: `ENV_redis_host`, `ENV_redis_port`, `ENV_redis_password`. Из них в `config/services.yaml` собирается `REDIS_URL` (по умолчанию `redis://default:%env(ENV_redis_password)%@%env(ENV_redis_host)%:%env(ENV_redis_port)%`); приложение и инфраструктура используют именно `REDIS_URL`. Дефолты оставлены и для CI: в `qa:behat` Symfony Dotenv грузит только `.env`, и эти значения совпадают с Redis-сервисом, который поднимается рядом с MySQL.
- Приложение: `ENV_app_secret`.

Важно:

- production secrets задаются через переменные CI/CD и/или на сервере, а не в репозитории;
- значения в `.env` считаются шаблонными и должны быть заменены в реальной инфраструктуре в файле `.env.local`;
- `ENV_redis_password` и `ENV_database_root_password` валидируются `prepare-mainline:env` через `REQUIRED_VARS` — пайплайн упадёт, если они не заданы в GitLab Variables (см. [`DEPLOYMENT.md`](../../DEPLOYMENT.md)).
