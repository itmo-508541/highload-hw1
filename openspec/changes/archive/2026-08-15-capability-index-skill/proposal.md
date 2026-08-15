## Why

AI-агенты тратят токены на поиск файлов реализации (Glob, Grep, Read),
вместо того чтобы обращаться к предвычисленному указателю. Проекту нужен
коммитируемый Capability Index — скилл, который хранит маппинг
«тематика spec.md → файлы реализации + их роли + точки входа» и предоставляет
агенту односторонний путь навигации: домены → тематики → файлы.

Тип: новая capability (tooling).

## What Changes

- Создаётся Cursor-скилл `capability-index` в `.cursor/skills/capability-index/`.
- Скилл содержит `SKILL.md` (инструкции агенту), `INDEX.md` (домены + quick reference)
  и каталог `topics/` с per-topic YAML-файлами (файлы реализации, роли, entry points, тесты).
- Агент получает read-only доступ к индексу для навигации; обновление — ответственность Архиватора.

## Capabilities

### New Capabilities
- `capability-index`: Структура, формат и правила использования Capability Index — скилла, хранящего маппинг specs → implementation files.

### Modified Capabilities
_(нет изменений в существующих requirements)_

## Impact

- Новые файлы: `.cursor/skills/capability-index/SKILL.md`, `INDEX.md`, `topics/*.yaml`.
- Зависимости: нет новых пакетов; формат YAML читается агентом нативно.
- Связанные процессы: Архиватор обновляет индекс при archive (интеграция — вне scope данного change).
