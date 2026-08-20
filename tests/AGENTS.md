# tests/

PHPUnit unit/integration-тесты.

## Конфигурация

- `phpunit.dist.xml`, bootstrap: `tests/bootstrap.php` (Composer + Dotenv).
- `APP_ENV=test` (задано в конфиге PHPUnit).
- Suite `Unit` (exclude `tests/E2E/`), suite `E2E` (`tests/E2E/`).

## Конвенции

- Namespace: `App\…` → `App\Tests\…` (зеркальная структура).
- Фикстуры: `./fixtures/` относительно `*Test.php`.
- Базовые классы и стиль assertions — от соседних тестов в том же каталоге.
- При наличии активного OpenSpec change — specs задают ожидаемое поведение
  для тестов.

## Команды

```bash
make test                             # suite Unit
make test-e2e                         # suite E2E (нужен Redis)
make test tests/Path/FooTest.php      # один файл
```

Skill: `.cursor/skills/phpunit-tests`. E2E runbook: `docs/e2e.md`.

## Куда не класть

- Gherkin `.feature`, `*Context.php` → `features/`
- OpenSpec specs → `openspec/`
