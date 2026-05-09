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

В CI должны быть заданы (Settings → CI/CD → Variables, флаги Protected/Masked по применимости):

- `SERVER_NAME`
- `DEPLOY_USER`
- `DEPLOY_HOST`
- `DEPLOY_DIRECTORY`
- `SSH_PRIVATE_KEY`
- `ENV_database_root_password` — root-пароль MySQL; используется при создании БД и пользователя через `phing database-deploy` и при интерполяции `${...}` в `docker-compose.deploy.yml`.
- `ENV_redis_password` — пароль Redis; попадает в `--requirepass` сервиса `redis` и в `REDIS_URL` приложения.

Список проверяется `prepare-mainline:env` через `REQUIRED_VARS` (см. [`.gitlab-ci.yml`](.gitlab-ci.yml)) — пайплайн падает на стадии `.pre`, если хотя бы одна переменная не задана.

> Любая переменная с префиксом `ENV_*` шаблоном `.prepare-env` попадает в `.env.local`, который потом летит на сервер. Чтобы переменная была доступна ещё и для интерполяции `${...}` в `docker-compose.deploy.yml` (например, `--requirepass ${ENV_redis_password}`), её нужно завести именно в GitLab Variables: команда `docker compose config` запускается на CI-job-е и читает окружение оттуда, а не из `.env.local` сервера.

Также используется доступ к GitLab Container Registry через CI-токены.

### 1) Подготовка окружения

Job `prepare-mainline:env`:
- собирает `.env.local` из переменных `ENV_*`;
- валидирует обязательные переменные для деплоя.

### 2) Сборка артефактов и образов

Для `master`/`production` собираются:
- Git image для deploy-шага;
- source image;
- app image (`prod` target);
- nginx image;
- сгенерированный `build/docker-compose.yml` из `docker-compose.deploy.yml`.

### 3) QA-блок (обязательный gate)

Перед деплоем должны успешно завершиться:
- `qa:php-cs-fixer`
- `qa:phpstan`
- `qa:rector`
- `qa:phpunit`
- `qa:behat`

### 4) Копирование release на удаленный сервер

Job `deploy-mainline:secure-copy`:
- по SSH создает временный каталог `${DEPLOY_DIRECTORY}_tmp`;
- копирует туда build-артефакты (`scp build/*`);
- атомарно переключает директорию release: удаляет старую и переименовывает `_tmp` в `${DEPLOY_DIRECTORY}`.

### 5) Запуск контейнеров и миграций на сервере

После переключения release на сервере выполняется:
- `docker compose pull`
- `docker compose up -d --no-recreate mysql redis`
- `docker compose up -d --force-recreate --no-deps spare`
- `docker compose exec -T spare phing database-deploy -Dapp.env=prod`
- `docker compose up -d --force-recreate --no-deps php`

Смысл `spare`: прогон миграций и сокращение downtime при деплое.

`mysql` и `redis` поднимаются явно одной командой `up -d --no-recreate` до пересоздания `spare`/`php`, чтобы Redis был доступен к моменту, когда `php-fpm` начнёт обслуживать первые HTTP-запросы (после рестарта `nginx` в шаге 6). Persistence через именованный том `redis-data` — активные пользовательские сессии переживают редеплой.

### 6) Перезапуск web-уровня

Финальный шаг:
- `docker compose stop nginx`
- `docker compose up -d nginx`

После этого новый backend-код начинает обслуживаться через nginx.

## Диаграмма CI/CD деплоя

```mermaid
flowchart TD
  prepareMainlineEnv[prepare-mainline-env] --> buildMainlineImages[build-mainline-images]
  buildMainlineImages --> qaChecks[qa-checks]
  qaChecks --> buildComposeArtifact[build-compose-artifact]
  buildComposeArtifact --> deploySecureCopy[deploy-secure-copy]
  deploySecureCopy --> remotePull[remote-pull-images]
  remotePull --> runSpareMigrations[run-spare-migrations]
  runSpareMigrations --> recreatePhp[recreate-php]
  recreatePhp --> restartNginx[restart-nginx]
```
