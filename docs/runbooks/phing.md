# Phing (операционный runbook)

Инициализация через Phing, загрузка env в `build.xml`, подготовка test-env для
Behat. Цепочка Dotenv приложения — в [`dotenv.md`](dotenv.md). Быстрый старт —
[`README.md`](../../README.md). PHP и Phing — только в Docker-сервисе `php`.

## Первый запуск и database-deploy

```bash
make up
make init
```

`make init` запускает Phing внутри контейнера `php` и приводит к подготовке БД
и миграциям (`database-deploy`).

Зависимости Composer (не Phing, но рядом в цикле):

```bash
make deps
# или: docker compose exec php composer install
```

## Как Phing получает те же переменные, что приложение

В Phing (`build.xml`) env подгружает не сам Phing, а
`Symfony\Component\Dotenv\Dotenv::bootEnv()` — её вызывает bootstrap из
`<autoloader autoloaderpath="tools/phing-bootstrap.php" />`.

Bootstrap:

1. Читает `-Dapp.env=...` (по умолчанию `dev`) и `-Dapp.debug=...`.
2. Выставляет `$_SERVER['APP_ENV']` / `$_SERVER['APP_DEBUG']`.
3. Вызывает `bootEnv` от корня репозитория — как Symfony Runtime для приложения.
4. После загрузки в `SYMFONY_DOTENV_VARS` дописываются `APP_ENV` / `APP_DEBUG`
   (`bootEnv` сама их туда не добавляет, если они уже были в `$_ENV`).

Задача `symfonyenv` (`tools/dotEnvPropertyTask.php`) копирует имена из
`$_SERVER['SYMFONY_DOTENV_VARS']` в свойства Phing с префиксом `dotenv`
(значения из `$_SERVER`, без `getenv` / `putenv`).

В `build.xml` обращения вида `${dotenv.ENV_database_host}` или
`${dotenv.APP_ENV}` соответствуют тому, что увидит приложение через Runtime — в
том числе при **`.env.local.php`** (локально, в тестах, или на проде через шим
`/srv/.env.local.php` → секрет; см. [`dotenv.md`](dotenv.md)).

## Behat и test-env

Цель `make behat` перед `vendor/bin/behat` запускает Phing с `-Dapp.env=test`,
чтобы подготовить тестовое окружение и доступ к БД (как в CI).

```bash
make behat
make behat features/phing_dotenv_property_task.feature
make behat ARGS="features/phing_dotenv_property_task.feature"
```

Конфигурация: `behat.yml.dist` (локальные правки — в некоммитируемый
`behat.yml`). Подробнее — skill `behat-tests`.

Прямой вызов без Makefile (контейнер `php` уже запущен):

```bash
docker compose exec php vendor/bin/behat
docker compose exec php vendor/bin/behat features/phing_dotenv_property_task.feature
```

## Связанные runbook'и

- Dotenv / prod-шим — [`dotenv.md`](dotenv.md)
- Redis (для app-сессий; Behat локально Redis не требует) — [`redis.md`](redis.md)
