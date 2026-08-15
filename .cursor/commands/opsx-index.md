---
name: "/opsx-index"
id: "opsx-index"
category: "Workflow"
description: "Индексировать архивированный OpenSpec change в Capability Index"
---

Обновить Capability Index (`.cursor/skills/capability-index/`) по **уже
архивированному** OpenSpec change. Команда пишет `topics/*.yaml` и при
необходимости `INDEX.md`. Коммит не создавать.

Повторный запуск по тому же архиву — переиндексация: то же слияние, не замена
файла целиком.

**Корень OpenSpec:** ближайший `openspec/` в репозитории (без `openspec list`
и `openspec status` — CLI не видит архивы).

**Input:** необязательное имя после `/opsx-index` (папка архива или имя change,
например `/opsx-index 2026-08-15-capability-index-skill` или
`/opsx-index capability-index-skill`). Если опущено — вывести из контекста
чата (только что архивировали) или спросить.

Всегда объяви: «Using archive: \<folder\>» и как переопределить
(`/opsx-index <other>`).

## Guardrails

- Писать индекс разрешено **только** этой команде. Вне `/opsx-index` файлы
  скилла capability-index не создавать и не менять.
- Не изменять `.cursor/skills/openspec-*` и `.cursor/commands/opsx-archive.md`.
- Не угадывать историю git. Не делать `git log` / поиск «какой коммит похож
  на этот change».
- Не копировать `operations.archive.guidance` в шаблон Archive Complete
  (это про `/opsx-archive`, не про эту команду).

## Steps

### 1. Выбрать архив

Каталог архивов: `openspec/changes/archive/`.

1. Если имя задано — найти папку:
   - точное совпадение `openspec/changes/archive/<name>/`;
   - иначе единственный префикс `YYYY-MM-DD-<name>/`;
   - если несколько совпадений — спросить.
2. Если имя не задано — взять архив из контекста чата (путь
   «Archived to: …» / только что завершённый `/opsx-archive`).
3. Если не вывести — листинг `openspec/changes/archive/` (не `openspec list`)
   и спросить Архиватора.

**Отказ (незаархивированный change):** если цель — `openspec/changes/<name>/`
(не внутри `archive/`), active change, или путь вне `openspec/changes/archive/`
— **остановиться**. Индекс не писать. Сообщить отказ: индексировать можно
только архивированный change.

### 2. Собрать затронутые spec

Найти все `openspec/changes/archive/<folder>/specs/**/spec.md`.

- **Ноль файлов** → отказ: индексация без изменений в `openspec/specs/`
  запрещена. Индекс не писать.
- Иначе — **все** найденные spec (несколько spec в одном change — все).
- Topic = путь каталога относительно `specs/` (например `qa`,
  `capability-index`).

Для каждого topic проверить main spec:
`openspec/specs/<topic>/spec.md`.

- Если main spec нет → отказ: archive без sync, main specs не изменились.
  Индекс не писать.

Семантика и `summary` — из **main** spec (Purpose / Requirements), не из
delta в архиве.

### 3. Источники реализации

Нужна картина файлов, которыми change реализован (paths, смысл правок).

- **Если в этом чате уже есть картина** (apply, diff, список файлов,
  обсуждение реализации) — использовать её. Коммиты **не** спрашивать.
- **Если картины нет** — запросить у Архиватора коммиты реализации
  (SHA и/или range) и **остановиться**, пока их не укажут. Индекс не писать
  до ответа. Не искать коммиты самостоятельно в git-истории.
- Когда коммиты указаны — читать только их: `git show` / `git diff` по
  названным SHA. Это разбор ввода Архиватора, не угадывание.

`tasks.md` архива и Impact в `proposal.md` — вспомогательный список path,
не замена контекста или коммитов.

### 4. Для каждого topic — merge yaml

Путь: `.cursor/skills/capability-index/topics/<topic>.yaml`
(имя файла = имя каталога spec).

Существующий yaml — baseline (повторный запуск тоже merge, не overwrite).

1. Прочитать main spec → `summary` из Purpose, семантика Requirements.
2. Собрать paths из источников шага 3 (и при необходимости tasks.md).
   Разделить implementation / tests.
3. Path, которого нет на диске — **не добавлять**.
4. Для path этого change: добавить или **обновить** `role`, entry_points,
   tests по текущему main spec.
5. Path, которых этот change не касался — **оставить**.
6. Поведение, снятое spec этого change: связанные записи **этого** change
   можно изменить или убрать.
7. Entry points: смотреть только файлы из источников (route / command /
   event / cron / message). Не сканировать весь репозиторий.
8. Записать yaml в формате ниже.

### 5. INDEX.md

Файл: `.cursor/skills/capability-index/INDEX.md`.

- **Новый topic:** строка в таблице Topics (`topic`, spec path, summary);
  domain из Purpose main spec — существующий домен, если подходит, иначе
  новая строка Domains. Связь domain→topic только в INDEX.md.
- **Существующий topic:** обновить summary при изменении Purpose; чужие
  строки и чужие домены не трогать; не перекладывать topic по доменам
  без явного сдвига Purpose.

### 6. Итог

Сообщить: archive, список обновлённых/созданных topic, отказ если был.
Коммит не делать — Архиватор видит diff в UI Cursor.

## Формат `topics/<topic>.yaml`

```yaml
topic: <string>          # = имя каталога в openspec/specs/
spec: <path>             # относительно корня репо, напр. openspec/specs/qa/spec.md
summary: <string>        # 1 предложение из Purpose main spec

files:
  - path: <path>
    role: <string>       # свободный текст, не таксономия

entry_points:
  - type: <enum>         # route | command | event | cron | message
    path: <string>       # для route/command/cron
    trigger: <string>    # для event/message (вместо path)
    handler: <string>

tests:
  - path: <path>
    role: <string>
```

`path` и `trigger` взаимоисключающие. Пустые списки допустимы (`files: []`).
Домен в yaml не писать.
