---
name: run-php-in-docker
description: Запускает команды PHP и связанных инструментов только в Docker-сервисе `php` из корня репозитория: приоритет целей `make`, иначе `docker compose exec php`. Использовать при Symfony Console, Composer, PHPUnit, Behat, Phing, PHP CS Fixer, PHPStan, Rector, при упоминании контейнера `php`, `make up`, или когда агент собирается выполнить PHP/Composer на хосте.
---

# Запуск PHP-команд в Docker

## Правило

Все команды, затрагивающие **PHP-код** и **PHP-окружение** (и бинарники из `vendor/bin/`), выполняй **только** внутри сервиса **`php`**. Не запускай `php`, `composer` и скрипты из `vendor/bin/` **на хосте** из корня репозитория — версии PHP, расширений и пути не совпадут с CI/продом.

Рабочая директория: **корень репозитория** на хосте; в контейнере проект смонтирован в `/srv/` (пути к файлам те же).

## Порядок выбора команды

1. Контейнеры подняты: `make up` (если не запущены).
2. **Сначала** ищи цель в **`Makefile`**.
3. Если готовой цели нет — **`docker compose exec php <команда>`** (из корня репозитория).

Для неинтерактивного ввода в скриптах/CI к `exec` иногда добавляют `-T` (см. цель `test` в `Makefile`).

## Эталонные цели `make`

| Задача | Команда |
|--------|---------|
| Поднять контейнеры | `make up` |
| Инициализация (Phing: БД, миграции) | `make init` |
| Зависимости Composer | `make deps` |
| PHPUnit | `make test` (Unit); `make test-e2e` (E2E) — узкий прогон: `make test ARGS="..."` |
| Behat | `make behat` (default); `make behat-e2e` (E2E / Mink+Panther) |
| PHP CS Fixer | `make cs` |
| PHPStan | `make phpstan` |
| Rector | `make rector` |
| Полный QA | `make qa` |
| Владелец файлов на хосте после работы от root в контейнере | `make owner` |

## Прямые вызовы (если `make` не подходит)

Все из **корня репозитория**, контейнер **`php`** запущен:

```bash
docker compose exec php composer <args>
docker compose exec php bin/console <args>
docker compose exec php phing <args>
docker compose exec php vendor/bin/phpunit <args>
docker compose exec php vendor/bin/behat <args>
docker compose exec php vendor/bin/php-cs-fixer <args>
docker compose exec php vendor/bin/phpstan <args>
docker compose exec php vendor/bin/rector <args>
```

## Заметки

- **Behat:** цели `make behat` / `make behat-e2e` перед `behat` вызывают Phing с `-Dapp.env=test` (как в CI); приложение под Panther в E2E — `APP_ENV=e2e` из `behat.yml.dist`. Предпочитай Makefile, а не голый `vendor/bin/behat`.
- **E2E:** нужен Redis в compose; детали — `docs/e2e.md`.
- **Права на хосте:** команды в контейнере часто от `root`; новые файлы в смонтированных каталогах могут стать `root` на хосте — см. `make owner`.
