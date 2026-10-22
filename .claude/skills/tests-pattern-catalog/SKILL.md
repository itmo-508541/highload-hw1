---
name: tests-pattern-catalog
description: >
  По указанию человека дополняет каталог patterns/tests/ карточками
  механик тестов. Use when the user explicitly asks to add or update
  a test-pattern card after reviewing tests (IDE code-review findings
  and optionally another chat).
disable-model-invocation: true
---

# Tests pattern catalog

Пополняет `patterns/tests/`. Не ищет баги заново: берёт уже собранный
code-review среды и то, на что указал человек.

## Когда работать

Только если человек явно указал класс ошибки или какую карточку сделать.
Без указания — не писать файлы в каталог.

## Входы

1. Указание человека (обязательно).
2. Результат code-review среды (если есть).
3. Другой чат с рассуждениями модели (если есть; у давно написанного теста чата нет).

Не запускать повторный разбор кода «как code-review».

## Шаги

1. Прочитать `patterns/tests/CLAUDE.md`, `INDEX.md`, `tests-pattern-card.md`.
2. Скопировать `tests-pattern-card.md` в новый файл (или расширить существующую карточку, если человек так сказал).
3. Сжать удачный или исправленный тест до donor.
4. Добавить одну строку в `INDEX.md`.
5. Не писать и не переписывать продуктовые тесты в этом прогоне, если человек не попросил отдельно.

## Не делать

- Упоминать скилл написания тестов или звать его из этого скилла.
