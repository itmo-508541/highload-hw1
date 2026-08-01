---
name: phpunit-tests
description: Описывает написание и обновление PHPUnit-тестов в Symfony-проекте и их запуск через Makefile в Docker. Использовать когда план или задача требует unit/integration-тестов, меняется код в `src/`, пользователь просит прогнать или добавить тесты, упоминаются `make test`, `phpunit.dist.xml` или каталог `tests/`.
---

# PHPUnit

## Контекст

- **Источник ожидаемого поведения:** OpenSpec — active change
  (`openspec/changes/<name>/specs/**`) и/или main specs (`openspec/specs/**`).
  Requirement/Scenario задают *что* должно быть верно; PHPUnit проверяет это
  на уровне кода. Не дублировать длинные specs wholesale в docblock'и тестов.
- Конфигурация: `phpunit.dist.xml`, bootstrap: `tests/bootstrap.php`, окружение тестов: `APP_ENV=test` (задано в конфиге PHPUnit).
- Тесты: каталог `tests/`, пространства имён вида `App\Tests\...` для классов из `App\...`.
- Запуск **только в Docker**: контейнер `php` должен быть поднят (`make up`). На хосте без контейнера не полагаться на совпадение PHP/расширений с CI.
- Прямой `docker compose exec`: для неинтерактивного режима (скрипты, агенты, CI) используйте **`exec -T`** — без псевдо-TTY, как в цели `make test` в `Makefile`.
- **Не смешивать каталоги:** OpenSpec specs остаются в `openspec/`; исполняемые
  Behat `.feature` — в `features/`; PHPUnit — только в `tests/`.

## Рабочий цикл

1. Перед написанием/правкой тестов прочитать затронутые OpenSpec specs (change
   delta и/или main): какие Scenario должны быть покрыты unit/integration.
2. Определить класс(ы) в `src/`, которые нужно покрыть или обновить.
3. Найти существующий тест в `tests/` по зеркальной структуре (например `src/EventListener/Foo.php` → `tests/EventListener/FooTest.php`) или создать новый `*Test.php`.
4. Использовать `PHPUnit\Framework\TestCase` или интеграционные базовые классы Symfony из проекта, если они уже приняты в соседних тестах — **сохранять стиль существующих тестов** в том же каталоге.
5. В `tasks.md` change (если ведёте OpenSpec) указывать пути к затронутым
   `*Test.php` и связь со Scenario, где уместно.
6. Запустить прогон:
   - весь набор: `make test`
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
