# tests/

PHPUnit unit/integration-тесты. Конфиг: `phpunit.dist.xml`, bootstrap:
`tests/bootstrap.php`. Пространства имён зеркалят `App\…` → `App\Tests\…`.

**Источник ожидаемого поведения** — OpenSpec specs (`openspec/specs/**` и
delta в change). PHPUnit подтверждает Scenario на уровне кода; change без
UI/приёмки часто покрывается только этим слоем.

Запуск: `make test` (suite Unit); E2E — `make test-e2e` (suite E2E,
`tests/E2E/`). Только Docker-сервис `php`.
Skill: `.cursor/skills/phpunit-tests`. E2E runbook: `docs/e2e.md`.
Процесс слоёв: `openspec/process.md`.

Behat-контексты проекта могут жить под `tests/` (например `App\Tests\Behat`),
но **исполняемые** `.feature` остаются в `features/` (E2E — `features/e2e/`).

## Куда не класть

- Gherkin `.feature` → `features/` / `features/e2e/` (не в `openspec/specs/`).
- OpenSpec specs / delta → `openspec/specs/`, `openspec/changes/`.
- Запуск PHPUnit на хосте без контейнера `php` — не делать.
