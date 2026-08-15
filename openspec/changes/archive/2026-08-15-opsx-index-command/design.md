## Context

Скилл `capability-index` уже хранит INDEX.md и `topics/*.yaml`. Контракт
обновления в main spec всё ещё привязан к archive; сгенерированный
`/opsx-archive` индекс не трогает. CLI `openspec status` / `list` не видят
заархивированные change — после `mv` в `openspec/changes/archive/` change
для CLI не существует. See proposal.md — Why; delta spec — requirements.

Формат topic-файла уже зафиксирован прошлым change (YAML: `topic`, `spec`,
`summary`, `files`, `entry_points`, `tests`). Этот design не меняет схему,
только процедуру записи.

## Goals / Non-Goals

**Goals:**
- Процедура записи целиком в `.cursor/commands/opsx-index.md`.
- Напоминание после archive без правок сгенерированных opsx-файлов.
- Скилл `capability-index` остаётся коротким read-only навигатором.

**Non-Goals:**
- Новый скилл-писатель.
- Правки `.cursor/skills/openspec-*` и `.cursor/commands/opsx-archive.md`.
- Полный пересчёт всех specs одним запуском (каталог целиком).
- Symbol-level индексация (AST, call graph).
- Автопроверка актуальности индекса.
- HITL-подтверждение yaml в чате (достаточно diff в UI Cursor).

## Decisions

### 1. Только команда, без нового скилла

**Выбор**: алгоритм записи живёт в `/opsx-index` (как `/adr`), не в скилле.

**Альтернативы**:
- Скилл + команда (как opsx-archive) → лишняя сущность; потребитель индекса
  может случайно подхватить write-инструкции.
- Write-секция в `capability-index/SKILL.md` → скилл перестаёт быть лёгким,
  read-only смешивается с записью.

**Обоснование**: потребителю индекса процедура записи не нужна. Архиватор
вызывает команду явно.

### 2. Напоминание через `operations.archive.guidance`

**Выбор**: добавить ключ в `openspec/config.yaml`. Формулировка — **действие**,
не цитата для summary: после успеха напомнить запустить `/opsx-index` с именем
папки архива; не вставлять текст guidance в шаблон «Archive Complete».

**Альтернативы**:
- Вшить шаг в `/opsx-archive` → файл перезаписывается `openspec update`.
- Ничего не говорить после archive → Архиватор из того же чата легко забудет.

**Обоснование**: архивный скилл уже читает `openspec instructions archive --json`
→ `operationGuidance` и следует применимым пунктам, не копируя их в summary.

### 3. Резолв цели по каталогу `archive/`, не по CLI

**Выбор**: аргумент `/opsx-index <имя>` или вывод из контекста чата (только что
архивировали). Каталог MUST быть под `openspec/changes/archive/`. Путь
`openspec/changes/<name>/` (active) — отказ. Если имя без даты — сопоставить
с `YYYY-MM-DD-<name>`; при неоднозначности спросить. Список архивов — листинг
каталога, не `openspec list`.

**Альтернативы**: полагаться на `openspec status --change` → для архива CLI
отвечает «not found».

### 4. Affected topics = `archive/.../specs/**/spec.md`

**Выбор**: нет ни одного spec.md в архиве → отказ (индексация без изменений
`openspec/specs/` запрещена). Иначе цикл по **всем** найденным spec.

Семантика и `summary` — из **main** spec (`openspec/specs/<topic>/spec.md`)
после sync, не из delta в архиве. Если main spec для новой тематики отсутствует
(archive без sync) → отказ: main specs не изменились.

**Альтернативы**: git diff vs main → после archive diff пуст. Индексировать
по delta в архиве → yaml указывал бы на устаревший или ещё не влитый spec.

### 5. Источник реализации: контекст чата или коммиты Архиватора

**Выбор**:
- Если в этом чате уже есть картина реализации (apply, diff, список файлов) —
  использовать её, коммиты не спрашивать.
- Иначе — запросить коммиты (SHA и/или range) и **остановиться**, пока
  Архиватор их не укажет. Не делать `git log` / поиск «какой коммит похож
  на этот change».
- Указанные коммиты читать (`git show` / `git diff` по ним) — это не угадывание,
  а разбор того, что назвал человек.

`tasks.md` архива — вспомогательный список path, не замена коммитов/контекста.

**Альтернативы**: «только файлы архива» → для маленького change в том же чате
лишний ритуал. Самостоятельный git-детектив → ответственность уезжает с
Архиватора на модель.

### 6. Merge по path, как sync spec.md

**Выбор**: существующий `topics/<topic>.yaml` — baseline.
- Path из источников этого change: добавить или **обновить** `role` /
  entry_points / tests по текущему main spec.
- Path, которых change не касался: не удалять.
- Path из источников, которых уже нет на диске: не добавлять.
- Поведение, снятое spec этого change: связанные записи **этого** change
  можно изменить или убрать.
- Новый topic: создать yaml, строку в таблице Topics, domain из Purpose
  (существующий домен, если подходит, иначе новая строка Domains).
- Повторный `/opsx-index` того же архива — тот же merge, не overwrite файла.

Не показывать отдельный HITL-опрос по yaml: Архиватор видит правки в UI Cursor.

### 7. Одна строка в SKILL.md

**Выбор**: read-only + «обновление — команда `/opsx-index`». Про archive не
упоминать. Алгоритм в скилл не копировать.

## Алгоритм `/opsx-index` (для текста команды)

1. Выбрать архив (аргумент / контекст чата / спросить список `archive/`).
2. Отказ, если цель не в `archive/`.
3. Собрать `archive/.../specs/**/spec.md`. Пусто → отказ.
4. Для каждого topic проверить, что main spec существует (иначе отказ: нет
   изменений в `openspec/specs/`).
5. Взять реализацию: контекст чата **или** запрошенные коммиты (ждать ответа).
6. Для каждого topic: прочитать main spec + существующий yaml; собрать files /
   tests / entry_points из источников; merge; записать yaml.
7. INDEX.md: новые строки и summary; domain — только для новых topic.
8. Краткий итог в чате (какие topic обновлены). Коммит не делать.

Формат yaml — как в текущих `topics/*.yaml` (поля из spec «Per-topic YAML»).

## Risks / Trade-offs

- **[Архиватор не запустит `/opsx-index`]** — индекс отстанет от specs.
  → Mitigation: `operations.archive.guidance`; ответственность роли, не агента.

- **[Неверные коммиты]** — в индекс попадут чужие файлы.
  → Mitigation: это осознанный ввод Архиватора; агент не подменяет выбор.

- **[Archive без sync]** — main spec не отражает delta.
  → Mitigation: отказ, если main spec для затронутого topic нет.

- **[Сгенерированный archive проигнорирует guidance]** — если пункт сочтёт
  конфликтующим с шаблоном summary.
  → Mitigation: действие «напомни отдельно», не правка шаблона Archive Complete.

## Migration Plan

Появление команды не требует миграции runtime. Откат — revert файлов change.
Существующие `topics/platform.yaml` и `topics/qa.yaml` не пересобирать в этом
change: их обновит `/opsx-index` по соответствующим архивам, когда Архиватор
запустит команду (в т.ч. в другом репозитории с большим набором specs).

`build/TOPICS.md` в репозиторий не входит и после реализации удаляется
Архиватором как черновик чата.

## Open Questions

_(нет — развилки закрыты в explore)_

## Затрагиваемые границы модулей

| Граница | Воздействие |
|---------|-------------|
| `.cursor/commands/` | Новый `opsx-index.md` |
| `.cursor/skills/capability-index/` | Одна строка в SKILL.md; yaml/INDEX пишет команда при запуске, не этот change |
| `openspec/config.yaml` | `operations.archive.guidance` |
| `openspec/specs/capability-index/` | Delta requirements (через archive этого change) |
| `.cursor/skills/openspec-*`, `opsx-archive.md` | Не трогать |
| Runtime PHP | Нет |

Архитектурное решение (ADR) не требуется — runtime приложения не затрагивается.

Brownfield-поведение PHP/legacy не меняется; раздел по `brownfield-map` не нужен.
