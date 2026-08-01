# tests/

PHPUnit unit/integration-тесты. Конфиг: `phpunit.dist.xml`, bootstrap:
`tests/bootstrap.php`. Пространства имён зеркалят `App\…` → `App\Tests\…`.

**Источник ожидаемого поведения** — OpenSpec specs (`openspec/specs/**` и
delta в change). PHPUnit подтверждает Scenario на уровне кода; change без
UI/приёмки часто покрывается только этим слоем.

Запуск: `make test` (только Docker-сервис `php`).
Skill: `.cursor/skills/phpunit-tests`. Процесс слоёв: `openspec/process.md`.

Behat-контексты проекта могут жить под `tests/` (например `App\Tests\Behat`),
но **исполняемые** `.feature` остаются в `features/`.

## Куда не класть

- Gherkin `.feature` → `features/` (не в `openspec/specs/`).
- OpenSpec specs / delta → `openspec/specs/`, `openspec/changes/`.
- Запуск PHPUnit на хосте без контейнера `php` — не делать.
