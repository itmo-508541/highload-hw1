# tests/

PHPUnit unit/integration-тесты. Конфиг: `phpunit.dist.xml`. **test env autoload:**
`tests/bootstrap.php` (Composer + Dotenv; тот же файл читает FoB в
`behat.yml.dist`). Пространства имён зеркалят `App\…` → `App\Tests\…`.

**Источник ожидаемого поведения** — OpenSpec specs (`openspec/specs/**` и
delta в change). PHPUnit подтверждает Scenario на уровне кода; change без
UI/приёмки часто покрывается только этим слоем.

Запуск: `make test` (suite Unit); E2E — `make test-e2e` (suite E2E,
`tests/E2E/`). Только Docker-сервис `php`.
Skill: `.cursor/skills/phpunit-tests`. E2E runbook: `docs/e2e.md`.

Фикстуры теста — `./fixtures/` относительно `*Test.php`. Каталог `tests/`
только для PHPUnit: контексты Behat сюда не класть.

## Куда не класть

- Gherkin `.feature` и `*Context.php` → `features/` / `features/bootstrap/`
  (E2E Gherkin — `features/e2e/`).
- OpenSpec specs / delta → `openspec/specs/`, `openspec/changes/`.
- Запуск PHPUnit на хосте без контейнера `php` — не делать.
