# Proposal

**Тип:** изменение brownfield-поведения (локальный demo-режим).

## Why

Demo-режим (`docker-compose.yml` без override, образ `target: prod`) задуман как
показ prod-поведения, но env в нём загружается не так, как на production: php
монтирует сырые `.env` и `.env.prod`, а на production приложение читает только
дамп `.env.local.php` через шим. Расхождение скрывает ошибки дампа, которые
всплывут лишь при деплое.

## What Changes

- Сервис `init` в demo становится обязательным: стартует первым, php ждёт его
  успешного завершения (`service_completed_successfully`); упавший `init`
  останавливает запуск.
- `init` при **каждом** старте demo заново собирает `.env.local.php` из цепочки
  `.env` → `.env.local` (если есть) → `.env.prod` → `.env.prod.local` (если
  есть) — порядок Symfony `loadEnv`, аналог CI-джобы
  `deploy-mainline:prepare-secrets` — и кладёт его в именованный том; затем
  выполняет `phing` (БД, миграции, assets), как сейчас. Без `.env` init падает.
- php в demo **не монтирует** `.env`-файлы: `/srv/.env.local.php` — шим, который
  подключает дамп из тома (тот же приём, что `secrets/env.php` на production).
  **BREAKING** для demo: правка `.env*` применяется только перезапуском, правка
  файлов внутри запущенного php больше не работает.
- Dev-режим: `init` выключен намеренно — профиль `disabled`, команда-заглушка
  `["false"]` (ничего не выполняет, при случайном запуске завершается ошибкой),
  без `user`. Инициализация dev — только вручную через `make` (`make init` →
  `exec php phing`, как раньше). `.env.local.php` не создаётся. В
  `docker-compose.override.yml.dist` php снимает зависимость от `init`.

## Capabilities

### New Capabilities

- нет

### Modified Capabilities

- `platform`: добавляется требование к локальному demo-режиму — env приложения
  берётся только из дампа, который `init` пересобирает при каждом старте, а
  запуск php зависит от успешного `init`; dev-режим этим требованием не
  затрагивается.

## Impact

- `docker-compose.yml`: порядок `init` → php, именованный том для дампа, шим
  вместо монтирования `.env`/`.env.prod` в php; `.env`-файлы монтируются только
  в `init`.
- `docker-compose.override.yml.dist`: `php.depends_on` без `init`; `init`
  выключен (`profiles: ["disabled"]`, `command: ["false"]`, без `user`).
- `Dockerfile`: каталог под том с владельцем `www-data` (иначе `init` не сможет
  писать в пустой том).
- Новый скрипт сборки дампа (в prod-образе нет composer, а `dotenv:dump` пишет
  только в `/srv`, недоступный для записи) и шим для demo.
- Документация: ADR `docs/adr/0005` — единственное место обоснований;
  `docs/runbooks/dotenv.md` и `README.md` дают команды и ссылаются на ADR.
- Не затрагиваются: поведение dev-режима (кроме выключения неиспользуемого
  `init`), test/e2e, CI-пайплайн, `docker-compose.deploy.yml`.
