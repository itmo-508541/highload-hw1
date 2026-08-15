## 1. Characterization

PHP/legacy runtime не затрагивается — characterization/regression PHPUnit и Behat
не требуются. Сценарии delta spec проверяются наличием явных шагов в команде
и конфиге (ниже, раздел 5), не автотестами приложения.

- [x] 1.1 Подтвердить отсутствие правок `src/`, `tests/`, `features/` в этом change

## 2. Команда `/opsx-index`

- [x] 2.1 Создать `.cursor/commands/opsx-index.md` (frontmatter name/id/description по образцу других opsx-команд проекта)
- [x] 2.2 Описать резолв архива: аргумент, контекст чата, листинг `openspec/changes/archive/`; не использовать `openspec list` / `status` как источник архивов
- [x] 2.3 Описать отказ, если цель не под `openspec/changes/archive/` (в т.ч. active `openspec/changes/<name>/`)
- [x] 2.4 Описать сбор `archive/.../specs/**/spec.md`: пусто → отказ; иначе цикл по всем; отказ, если нет соответствующего main spec
- [x] 2.5 Описать источники реализации: контекст чата если есть, иначе запросить коммиты и ждать; запрет самостоятельно искать коммиты в git-истории; `git show`/`diff` только по указанным SHA
- [x] 2.6 Описать merge `topics/<topic>.yaml` и правки INDEX.md (новые topic + summary + domain из Purpose); не overwrite; несуществующие path не добавлять; коммит не делать
- [x] 2.7 Включить в команду формат yaml (поля spec «Per-topic YAML») и алгоритм из design.md

## 3. Скилл capability-index

- [x] 3.1 В `SKILL.md` заменить строку про обновление: команда `/opsx-index`; про archive не упоминать; алгоритм записи не добавлять; read-only вне `/opsx-index` сохранить

## 4. Напоминание после archive

- [x] 4.1 Добавить `operations.archive.guidance` в `openspec/config.yaml`: после успеха напомнить `/opsx-index` с именем папки архива; не копировать текст в шаблон Archive Complete
- [x] 4.2 Не изменять `.cursor/skills/openspec-*` и `.cursor/commands/opsx-archive.md`

## 5. Покрытие Scenario (проверка текста команды и конфига)

Каждый пункт — Scenario из `specs/capability-index/spec.md`. PHPUnit/Behat
не применимы (нет runtime). Done = шаг явно есть в `opsx-index.md` или
`openspec/config.yaml`.

- [x] 5.1 Scenario «Агент не модифицирует индекс вне /opsx-index» — SKILL.md
- [x] 5.2 Scenario «Агент обновляет индекс по /opsx-index» — команда пишет yaml/INDEX
- [x] 5.3 Scenario «Индексация архивированного change»
- [x] 5.4 Scenario «Отказ для незаархивированного change»
- [x] 5.5 Scenario «Отказ без изменений spec»
- [x] 5.6 Scenario «Несколько spec в одном change»
- [x] 5.7 Scenario «Слияние с существующим topic-файлом»
- [x] 5.8 Scenario «Добавление новой тематики»
- [x] 5.9 Scenario «Повторный запуск переиндексирует»
- [x] 5.10 Scenario «Контекст чата используется без запроса коммитов»
- [x] 5.11 Scenario «Без контекста агент запрашивает коммиты»
- [x] 5.12 Scenario «Напоминание после archive» — `operations.archive.guidance`
