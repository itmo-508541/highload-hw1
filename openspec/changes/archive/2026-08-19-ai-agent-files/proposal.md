## Why

Cursor-команды в `.cursor/commands/` — устаревающий формат: и Cursor, и соседние агенты
сводят slash-вызов к skills с `disable-model-invocation: true`. В репозитории рядом
лежат три своих команды, динамическое правило без globs и скилл-обёртка
`update-rules`, содержимое которой дублирует маркеры каталогов. Нужно привести
свои агентские файлы к одному формату, не трогая generated OpenSpec-артефакты
и не меняя поведение продукта.

Тип: чистый refactor/tooling.

## What Changes

- Три своих команды становятся skills с `disable-model-invocation: true`
  (slash-имена те же): `/adr`, `/opsx-index`, `/opsx-terra-firma`.
- `add-docker-volume` остаётся скиллом; добавляется `disable-model-invocation: true`
  (чеклист тома — только по явному `/add-docker-volume`).
- Правило `vendor-class-lookup` (без globs) становится скиллом с автовызовом:
  разрешение FQCN через Composer, когда `vendor/` вне индекса из-за `.gitignore`.
- Скилл `update-rules` удаляется. Рецепт нового операционного runbook
  (плоский `docs/<name>.md`, таблица в `docs/AGENTS.md`, ссылка в README)
  переносится в `docs/AGENTS.md`, чтобы не потерять инструкцию.
- Не входит в этот change: перенос в `.agents/` / `.claude/`; gitignore на
  generated `openspec-*` / `opsx-*`; правки `run-php-in-docker.mdc` и
  `adr-immutability.mdc`.

## Capabilities

### New Capabilities
_(нет — `skip_specs: true` в `.openspec.yaml`)_

### Modified Capabilities
_(нет. Requirements `/opsx-index` и `/opsx-terra-firma` описывают slash-вызов
и запрет писать индекс/terra вне этой команды; путь файла-инструкции не является
requirement. Поведение продукта не меняется.)_

## Impact

- `.cursor/commands/adr.md`, `opsx-index.md`, `opsx-terra-firma.md` →
  `.cursor/skills/<name>/SKILL.md`; исходные command-файлы удаляются.
- `.cursor/rules/vendor-class-lookup.mdc` → `.cursor/skills/vendor-class-lookup/SKILL.md`.
- `.cursor/skills/add-docker-volume/SKILL.md` — frontmatter.
- Удаление `.cursor/skills/update-rules/`.
- Ссылки: `.cursor/AGENTS.md`, `adr/AGENTS.md`, `adr/0000-adr-template.md`,
  `docs/AGENTS.md`, `docs/volume.md` (если путь к скиллу),
  `.cursor/skills/capability-index/` (пути в SKILL.md / `topics/terra.yaml`).
- Сгенерированные `.cursor/commands/opsx-*.md` (кроме index и terra-firma) и
  `.cursor/skills/openspec-*` не изменяются.
- Код приложения, тесты, API, зависимости — без изменений.
