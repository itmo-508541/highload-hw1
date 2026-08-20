# Symfony Backend (шаблон проекта)

PHP 8.4 · Symfony 7.4 · MySQL 8.0 · Redis 8 · Docker Compose

## Constraints

- `composer`, `phing`, `php`, `bin/console`, `vendor/bin/*` — **только в Docker-сервисе `php`**.
  Shell: `required_permissions: ["all"]`. Предпочитать цели `Makefile`.
- Контейнеры не подняты → `make up`.
- Symfony Console fallback: `docker compose exec -T php bin/console <cmd>`.
- `vendor/` gitignored, не в индексе agent harness. Для поиска классов зависимостей — скилл `vendor-class-lookup`, не веб.
- Секреты production — не в репозитории.

## Каталоги

| Каталог | Назначение |
|---------|-----------|
| `src/` | Код приложения (Symfony) |
| `tests/` | PHPUnit unit/integration-тесты |
| `features/` | Behat приёмочные сценарии (Gherkin + контексты) |
| `migrations/` | Doctrine-миграции (только данные, не DDL) |
| `docs/` | Операционные runbook'и |
| `adr/` | Architecture Decision Records |
| `openspec/` | Спецификации поведения и changes (не смешивать с `features/`) |
| `vendor/` | Зависимости Composer (gitignored, не в индексе agent harness) |

## QA-гейт

`make qa` — запускает пользователь перед push.
