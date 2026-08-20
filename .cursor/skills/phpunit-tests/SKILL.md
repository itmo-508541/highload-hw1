---
name: phpunit-tests
description: >
  Пишет и обновляет PHPUnit-тесты (*Test.php в tests/, E2E в tests/E2E/);
  прогон — make test / make test-e2e. Использовать при unit/integration/E2E-
  тестах, изменении кода в src/, просьбе добавить или прогнать PHPUnit,
  упоминании phpunit.dist.xml, make test, make test-e2e, tests/E2E/, App\Tests.
---

# PHPUnit

Контекст и конвенции: `tests/AGENTS.md`.

## Рабочий цикл

1. Прочитать затронутые OpenSpec specs (если есть активный change).
2. Определить класс(ы) в `src/`, которые нужно покрыть.
3. Найти существующий тест по зеркальной структуре или создать `*Test.php`.
4. Сохранять стиль соседних тестов (базовые классы, assertions).
5. Запустить: `make test` (Unit), `make test-e2e` (E2E), один файл —
   `make test tests/Path/FooTest.php`.
6. Исправить падения до зелёного прогона (макс. 3 попытки). Если после
   третьей попытки тесты не зелёные — остановиться, сообщить пользователю
   о проблеме и ждать указаний.

## Когда достаточно PHPUnit

Change без UI/HTTP-приёмки: unit/integration в `tests/` обычно достаточно;
Behat не обязателен, если Scenario не требуют исполняемого Gherkin-потока.

## Справочник API

PHPUnit 13 Model Knowledge Patch: `PHPUnit13-full.md` в директории скилла —
прочитать при написании тестов.

## Чеклист

- [ ] Тесты в `tests/`, имена согласованы с проектом
- [ ] Фикстуры в `./fixtures/` относительно `*Test.php`
- [ ] `make test` пройден в Docker
- [ ] Пользователю указаны пути и результат прогона
