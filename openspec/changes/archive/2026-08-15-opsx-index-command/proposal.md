## Why

Capability Index уже есть, но контракт обновления неверный: spec требует
писать индекс «при archive», при этом интеграция в `/opsx-archive` сознательно
не делалась. Архиватор (роль человека с правом archive) не может инициировать
индексацию отдельной командой, а агент не знает, когда ему позволено писать
в скилл. Нужна ручная команда `/opsx-index` и правка requirements.

Тип: чистый tooling (изменение существующей capability).

## What Changes

- Появляется Cursor-команда `/opsx-index`: индексирует **уже архивированный**
  change (только что или ранее). Незаархивированный change команда MUST
  отвергать.
- Индексация запрещена, если в архиве нет изменений spec (`openspec/specs/`).
  Если spec несколько — индексируются все.
- Обновление индекса — **merge, не replace**: влить и при необходимости
  обновить записи этого change по текущему main spec; строки других change
  не выкидывать. Повторный запуск по тому же архиву — переиндексация.
- Источник реализации: контекст чата, если он уже есть; иначе агент MUST
  запросить у Архиватора коммиты реализации и не угадывать по git-истории.
- Сгенерированные скиллы/команды OpenSpec не меняются. Напоминание после
  archive — через `operations.archive.guidance` в `openspec/config.yaml`.
- Скилл `capability-index` остаётся лёгким read-only навигатором: одна строка
  про обновление через `/opsx-index`, без алгоритма записи.

## Capabilities

### New Capabilities
_(нет — команда операционализирует существующий индекс)_

### Modified Capabilities
- `capability-index`: момент и способ обновления индекса (не при archive,
  а командой `/opsx-index`); запрет на active change и на индексацию без
  изменений spec; merge вместо overwrite; исключение read-only для исполнения
  `/opsx-index`; ответственность Архиватора за указание коммитов при отсутствии
  контекста чата.

## Impact

- Новый файл: `.cursor/commands/opsx-index.md` (процедура записи целиком).
- Правки: `openspec/specs/capability-index/spec.md` (через delta),
  `.cursor/skills/capability-index/SKILL.md`, `openspec/config.yaml`
  (`operations.archive.guidance`).
- Не трогать: `.cursor/skills/openspec-*`, `.cursor/commands/opsx-archive.md`
  и прочие сгенерированные opsx-артефакты.
- Зависимости: нет новых пакетов.
- Runtime приложения не затрагивается.
