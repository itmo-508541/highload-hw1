## Context

See proposal.md — Why / What Changes.

Свои агентские инструкции живут в трёх форматах Cursor: `.cursor/commands/*.md`
(slash), `.cursor/skills/*/SKILL.md` (discovery + slash), `.cursor/rules/*.mdc`
(alwaysApply / globs). Граница change — только **свои** файлы в `.cursor/`,
плюс ссылки в `docs/AGENTS.md`, `adr/AGENTS.md`, шаблоне ADR и Capability Index.
`src/`, тесты, generated `openspec-*` / остальные `opsx-*` не входят.

Brownfield-поведение продукта не затрагивается — раздел brownfield-map не нужен.
Новый ADR не нужен: ADR-0001 фиксирует slash `/adr` и каталог `adr/`, не путь
`.cursor/commands/adr.md`. Тело принятого ADR не правим (иммутабельность);
битая ссылка в «Связанные артефакты» ADR-0001 остаётся как есть.

## Goals / Non-Goals

**Goals:**
- Единый формат своих вызываемых вручную процедур: `SKILL.md` +
  `disable-model-invocation: true`, slash-имя = имя каталога скилла.
- Инвариант записи индекса/terra: алгоритм в отдельном write-скилле, не в
  read-only `capability-index`.
- Рецепт нового runbook — в маркере `docs/AGENTS.md`, без скилла-обёртки.

**Non-Goals:**
- Канон `.agents/` / `.claude/` и symlink-стратегия.
- `.gitignore` на generated OpenSpec-файлы.
- `/migrate-to-skills` по всему `.cursor/commands/`.
- Правки `run-php-in-docker.mdc` и `adr-immutability.mdc`.
- Новый ADR или правка тела ADR-0001.

## Decisions

### 1. Команда → skill, не удаление процедуры

**Выбор**: тело `adr.md` / `opsx-index.md` / `opsx-terra-firma.md` переносится
в `.cursor/skills/<name>/SKILL.md`. Frontmatter: `name` без ведущего `/`
(совпадает с каталогом), `description` из команды, `disable-model-invocation: true`.
Поля `id` / `category` не копировать. Исходный файл в `commands/` удалить.

**Альтернативы**: оставить commands (формат устаревает); сливать запись в
`capability-index` (ломает read-only).

**Обоснование**: `/opsx-index` в чате остаётся `/opsx-index`. Предыдущий design
«алгоритм в команде, не в скилле» сохраняется семантически: потребитель индекса
не видит write-инструкции, пока Архиватор явно не вызовет скилл.

### 2. Имена каталогов = slash без префикса `/`

| Slash | Каталог скилла |
|-------|----------------|
| `/adr` | `.cursor/skills/adr/` |
| `/opsx-index` | `.cursor/skills/opsx-index/` |
| `/opsx-terra-firma` | `.cursor/skills/opsx-terra-firma/` |
| `/add-docker-volume` | `.cursor/skills/add-docker-volume/` (уже есть) |

Не называть `openspec-index`: сгенерированные скиллы — `openspec-*`, свои
write-процедуры — `opsx-*`.

### 3. Аргумент `/adr`: без `{{{ input }}}`

**Выбор**: плейсхолдер Cursor-команд в скилле не работает. В SKILL.md явно:
хвост сообщения после `/adr` — контекст решения; если пусто — спросить.

У `/opsx-index` и `/opsx-terra-firma` аргумент уже описан словами — перенести как есть.

### 4. `vendor-class-lookup` — skill с автовызовом

**Выбор**: тело текущего `.mdc` → `SKILL.md`; правило удалить. **Без**
`disable-model-invocation`: агент должен подхватить скилл, когда лезет в FQCN
при gitignored `vendor/`. `paths` не ставить (триггер — description, не открытый файл).

**Альтернативы**: оставить requestable rule (Cursor-only, хуже discovery);
slash-only (агент забудет вызвать).

### 5. `add-docker-volume` — только замок автовызова

**Выбор**: в существующий frontmatter добавить `disable-model-invocation: true`.
Путь скилла и `docs/volume.md` не менять.

### 6. `update-rules`: удалить, четыре строки — в `docs/AGENTS.md`

**Выбор**: дописать в `docs/AGENTS.md` рецепт: контур изменился → обновить или
добавить плоский `docs/<name>.md` (kebab-case латиницей, один контур);
строка в таблице этого же файла; при необходимости ссылка в README
«Документация». Затем удалить `.cursor/skills/update-rules/`.

Не переносить: п.1 про specs/README (уже в OpenSpec и AGENTS), п.3 про
`run-php-in-docker.mdc` (правило заморожено), запрет CHANGELOG (вне scope).

### 7. Ссылки, которые правим / не правим

Правим (живые маркеры и индекс):
- `.cursor/AGENTS.md` — `commands/` остаётся для generated `opsx-*`; свои
  slash-процедуры указать как skills.
- `adr/AGENTS.md`, `adr/0000-adr-template.md` — путь на `.cursor/skills/adr/SKILL.md`.
- `docs/AGENTS.md` — рецепт runbook.
- `capability-index/topics/terra.yaml` — `path` / `handler` на
  `.cursor/skills/opsx-terra-firma/SKILL.md`; `entry_points.path` `/opsx-terra-firma`
  не менять. Role: «скилл создания terra.md…».
- `capability-index/SKILL.md` — slash `/opsx-index` можно оставить; если есть
  путь к `commands/` — заменить.

Не правим: `openspec/specs/**` (requirement = slash), `openspec/config.yaml`
(там `/opsx-index` без пути файла), ADR-0001, архивы change, `docs/volume.md`.

Не запускать `/migrate-to-skills`: схватит generated `opsx-apply` и создаст дубль
рядом с `openspec-apply-change`.

## Risks / Trade-offs

- **[Агент забудет `/add-docker-volume`]** → чеклист compose/CI не применится.
  Mitigation: description скилла и `docs/volume.md` по-прежнему указывают на
  `/add-docker-volume`; это принятый замок, не баг.
- **[Агент не подхватит `vendor-class-lookup`]** → снова обход `vendor/` / веб.
  Mitigation: description с триггерами FQCN, autoload, classmap, `vendor/`.
- **[Битая ссылка в ADR-0001]** → принято: иммутабельность важнее правки пути.
  Живые ссылки — в `adr/AGENTS.md` и шаблоне 0000.
- **[Дубль slash после будущего `openspec init`]** → generated остаются в
  `commands/`; свои — в `skills/opsx-index`. Имена не пересекаются с
  `openspec-*`. Не мигрировать generated.
- **[После удаления трёх файлов `commands/` всё ещё не пуст]** → там init.
  `.cursor/AGENTS.md` это фиксирует. gitignore — отдельный change.

## Migration Plan

1. Создать три каталога скиллов, перенести тело, выставить frontmatter, удалить
   command-файлы.
2. Перенести `vendor-class-lookup`, удалить `.mdc`.
3. Флаг на `add-docker-volume`; правка `docs/AGENTS.md`; удалить `update-rules`.
4. Обновить ссылки из §7.
5. Проверить в UI Cursor, что `/adr`, `/opsx-index`, `/opsx-terra-firma`,
   `/add-docker-volume` видны как skills; generated `/opsx-apply` и т.д. на месте.
Rollback: git revert коммита change; generated файлы не затрагиваются.
