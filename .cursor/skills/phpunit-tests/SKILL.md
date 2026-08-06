---
name: phpunit-tests
description: >
  Пишет и обновляет PHPUnit-тесты (*Test.php в tests/, E2E в tests/E2E/);
  прогон — make test / make test-e2e. Использовать при unit/integration/E2E-
  тестах, изменении кода в src/, просьбе добавить или прогнать PHPUnit,
  упоминании phpunit.dist.xml, make test, make test-e2e, tests/E2E/, App\Tests.
---

# PHPUnit

## Контекст

- **Источник ожидаемого поведения:** OpenSpec — active change
  (`openspec/changes/<name>/specs/**`) и/или main specs (`openspec/specs/**`).
  Requirement/Scenario задают *что* должно быть верно; PHPUnit проверяет это
  на уровне кода. Не дублировать длинные specs wholesale в docblock'и тестов.
- Конфигурация: `phpunit.dist.xml`, bootstrap: `tests/bootstrap.php`, окружение Unit: `APP_ENV=test` (в конфиге PHPUnit). Suite: `Unit` (exclude `tests/E2E`) и `E2E`.
- Тесты: каталог `tests/` (Unit/integration); E2E — `tests/E2E/` (Panther / Redis-сессии). Пространства имён вида `App\Tests\...`.
- Запуск **только в Docker**: контейнер `php` должен быть поднят (`make up`). На хосте без контейнера не полагаться на совпадение PHP/расширений с CI.
- Прямой `docker compose exec`: для неинтерактивного режима (скрипты, агенты, CI) используйте **`exec -T`** — без псевдо-TTY, как в цели `make test` в `Makefile`.
- **Не смешивать каталоги:** OpenSpec specs — в `openspec/`; Behat — в `features/` / `features/e2e/`; PHPUnit Unit — в `tests/` вне `tests/E2E/`.

## Рабочий цикл

1. Перед написанием/правкой тестов прочитать затронутые OpenSpec specs (change
   delta и/или main): какие Scenario должны быть покрыты unit/integration.
2. Определить класс(ы) в `src/`, которые нужно покрыть или обновить.
3. Найти существующий тест в `tests/` по зеркальной структуре (например `src/EventListener/Foo.php` → `tests/EventListener/FooTest.php`) или создать новый `*Test.php`.
4. Использовать `PHPUnit\Framework\TestCase` или интеграционные базовые классы Symfony из проекта, если они уже приняты в соседних тестах — **сохранять стиль существующих тестов** в том же каталоге.
5. В `tasks.md` change (если ведёте OpenSpec) указывать пути к затронутым
   `*Test.php` и связь со Scenario, где уместно.
6. Запустить прогон:
   - Unit: `make test`
   - E2E: `make test-e2e` (нужен Redis; см. `docs/e2e.md`)
   - один файл: `make test tests/Path/To/SomeTest.php` или `make test ARGS="tests/Path/To/SomeTest.php"`
7. Исправить падения до зелёного прогона, если это в рамках задачи.

## Когда достаточно PHPUnit

Change без UI/HTTP-приёмки: unit/integration в `tests/` обычно достаточно;
Behat не обязателен, если Scenario не требуют исполняемого Gherkin-потока.
См. `openspec/process.md` (слои тестов).

## Команды

```bash
make test
make test tests/EventListener/CorsRequestListenerTest.php
make test ARGS="tests/EventListener/CorsRequestListenerTest.php"
```

Прямой вызов (если Makefile неудобен):

```bash
docker compose exec -T php vendor/bin/phpunit
docker compose exec -T php vendor/bin/phpunit tests/EventListener/CorsRequestListenerTest.php
```

## Мини-чеклист

- [ ] Прочитаны затронутые OpenSpec specs/change (источник поведения)
- [ ] Тесты лежат в `tests/`, имена классов и файлов согласованы с принятыми в проекте примерами
- [ ] Выполнен `make test` (или узкий прогон изменённого файла) в запущенном контейнере `php`
- [ ] Пользователю при сдаче указаны пути к тестам и результат прогона
