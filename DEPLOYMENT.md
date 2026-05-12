# Деплой через GitLab

## Правила веток и CI-гейт

- Pipeline запускается после push в любую ветку, но деплой выполняется только для mainline-веток: `master` и `production`.
- Для mainline перед деплоем обязательны успешные проверки:
  - `qa:php-cs-fixer`
  - `qa:phpstan`
  - `qa:rector`
  - `qa:phpunit`
  - `qa:behat`
- При падении любой QA-проверки деплой не начинается.

## Порядок деплоя на удаленный сервер

Ниже порядок, соответствующий текущему `.gitlab-ci.yml`.

### 0) Предусловия для mainline-деплоя

В CI должны быть заданы (Settings → CI/CD → Variables, флаги Protected/Masked по применимости).

**Переменные (тип Variable), проверяются `REQUIRED_VARS` в `prepare-mainline:env`:**

- `SERVER_NAME`
- `DEPLOY_USER`
- `DEPLOY_HOST`
- `DEPLOY_DIRECTORY`
- `ENV_app_secret`
- `ENV_database_password`
- `ENV_database_root_password` — root-пароль MySQL; используется при создании БД и пользователя через `phing database-deploy` и при интерполяции `${...}` в `docker-compose.deploy.yml`.
- `ENV_jwt_passphrase`
- `ENV_redis_password` — пароль Redis; попадает в `--requirepass` сервиса `redis`, в `REDISCLI_AUTH` и в резолв `REDIS_URL` для приложения.

**Файловые переменные (тип File), проверяются `REQUIRED_FILES`:**

- `JWT_PRIVATE_KEY`, `JWT_PUBLIC_KEY` — ключи JWT для копирования в `build/secrets/` на этапе подготовки секретов.
- `SSH_PRIVATE_KEY` — ключ для `deploy-mainline:secure-copy`.

Список обязательных имён задаётся в job `prepare-mainline:env` (см. [`.gitlab-ci.yml`](.gitlab-ci.yml)) — пайплайн падает на стадии `.pre`, если что-то не задано или файл недоступен.

**Как связаны `ENV_*`, CI и сервер:**

- В job `prepare-mainline:env` все переменные окружения с префиксом `ENV_*` записываются в артефакт **`.env.prod.local`** (dotenv-строки). Этот файл нужен дальше для **`composer symfony:dump-env prod`**: в итоговый PHP-дамп окружения попадают все prod-секреты и параметры, которые должны оказаться в **`.env.local.php`** на сервере.
- Интерполяция **`${...}`** в `docker-compose.deploy.yml` при выполнении **`docker compose … config`** на CI (job `build-mainline:docker-compose`) читает значения из **окружения того же CI-job**, то есть переменные с такими именами должны быть заведены в GitLab Variables (или иначе оказаться в `printenv` раннера). На сервере для этого отдельный `env_file` у сервисов приложения **не используется** — на сервер уезжает уже **отрендеренный** `build/docker-compose.yml` без `${…}`.
- На сервер в каталоге релиза копируются **`build/secrets/*.pem`**, **`build/secrets/.env.local.php`** (дамп) и **`build/env.php`** (шим для Symfony, см. ниже). В [docker-compose.deploy.yml](docker-compose.deploy.yml) для `php` / `spare` заданы **Docker secrets** (файлы под `./secrets/` на хосте релиза) и **config** `env.php` → **`/srv/.env.local.php`**: небольшой файл из репозитория [`secrets/env.php`](secrets/env.php) делает `return include '/run/secrets/.env.local.php';`, чтобы Symfony Runtime загружал стандартный путь **`.env.local.php`**, а фактические значения лежали в секрете.

Также используется доступ к GitLab Container Registry через CI-токены.

### 1) Подготовка окружения (`.pre`)

Job **`prepare-mainline:env`**:

- в `before_script` проверяет наличие и непустоту всех `REQUIRED_VARS` и читаемость файлов `REQUIRED_FILES`;
- в `script` создаёт **`.env.prod.local`**: для каждой переменной `ENV_*` из окружения job добавляет строку в файл;
- сохраняет **`.env.prod.local`** как артефакт для последующих job.

### 2) Сборка артефактов и образов

Для `master`/`production` собираются:

- Git image для deploy-шага;
- source image;
- app image (`prod` target);
- nginx image;
- **`build/docker-compose.yml`** — job `build-mainline:docker-compose` выполняет `docker compose -f docker-compose.deploy.yml config` в окружении CI и кладёт результат в `build/` (пути в YAML подменяются под layout на сервере).

### 3) QA-блок (обязательный gate)

Перед деплоем должны успешно завершиться:

- `qa:php-cs-fixer`
- `qa:phpstan`
- `qa:rector`
- `qa:phpunit`
- `qa:behat`

### 4) Подготовка секретов для релиза (`deploy`)

Job **`deploy-mainline:prepare-secrets`** (стадия `deploy`, после QA):

- подтягивает артефакт **`.env.prod.local`** из `prepare-mainline:env` и зависимости Composer из кэша;
- выполняет **`composer symfony:dump-env prod`** (читаются `.env`, `.env.prod`, `.env.prod.local` и т.д. по правилам Symfony);
- переносит сгенерированный **`.env.local.php`** в **`build/secrets/.env.local.php`**;
- копирует в **`build/`** шим **[`secrets/env.php`](secrets/env.php)** как **`build/env.php`** (его подхватывает `configs:` в compose на сервере);
- копирует ключи JWT в **`build/secrets/jwt_private.pem`** и **`build/secrets/jwt_public.pem`**;
- публикует артефакт **`build/`** (каталог `secrets/` внутри него и `env.php` в корне `build/`).

### 5) Копирование release на удаленный сервер

Job **`deploy-mainline:secure-copy`**:

- по `needs` получает артефакты **`build-mainline:docker-compose`** (в т.ч. `build/docker-compose.yml`) и **`deploy-mainline:prepare-secrets`** (`build/secrets/`, `build/env.php`) — содержимое каталогов сливается в рабочем каталоге job;
- по SSH создаёт временный каталог `${DEPLOY_DIRECTORY}_tmp`;
- копирует туда содержимое **`build/`** (`scp -r build/*`);
- атомарно переключает директорию release: удаляет старую и переименовывает `_tmp` в `${DEPLOY_DIRECTORY}`.

На сервере рядом с `docker-compose.yml` оказываются **`env.php`** и каталог **`secrets/`** с файлами, на которые ссылаются `configs:` и `secrets:` в сгенерированном compose.

### 6) Запуск контейнеров и миграций на сервере

После переключения release на сервере выполняется:

- `docker compose pull`
- `docker compose up -d --no-recreate mysql redis`
- `docker compose up -d --force-recreate --no-deps spare`
- `docker compose exec -T spare phing database-deploy -Dapp.env=prod`
- `docker compose up -d --force-recreate --no-deps php`

Смысл `spare`: прогон миграций и сокращение downtime при деплое.

`mysql` и `redis` поднимаются явно одной командой `up -d --no-recreate` до пересоздания `spare`/`php`, чтобы Redis был доступен к моменту, когда `php-fpm` начнёт обслуживать первые HTTP-запросы (после рестарта `nginx` в шаге 7). Persistence через именованный том `redis-data` — активные пользовательские сессии переживают редеплой.

### 7) Перезапуск web-уровня

Финальный шаг:

- `docker compose stop nginx`
- `docker compose up -d nginx`

После этого новый backend-код начинает обслуживаться через nginx.

## Диаграмма CI/CD деплоя

```mermaid
flowchart TD
  prepareMainlineEnv[prepare-mainline:env] --> buildMainline[build: images + compose yml]
  buildMainline --> qaChecks[qa-checks]
  qaChecks --> prepareSecrets[deploy-mainline:prepare-secrets]
  prepareSecrets --> deploySecureCopy[deploy-mainline:secure-copy]
  deploySecureCopy --> remotePull[remote-pull-images]
  remotePull --> runSpareMigrations[run-spare-migrations]
  runSpareMigrations --> recreatePhp[recreate-php]
  recreatePhp --> restartNginx[restart-nginx]
```
