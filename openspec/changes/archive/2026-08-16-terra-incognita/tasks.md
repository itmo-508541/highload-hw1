## 1. Characterization

PHP/legacy runtime не затрагивается — characterization/regression PHPUnit и Behat
не требуются. Сценарии delta spec проверяются наличием явных шагов в команде
и конфиге (ниже, раздел 4), не автотестами приложения.

- [x] 1.1 Подтвердить отсутствие правок `src/`, `tests/`, `features/` в этом change

## 2. Команда `/opsx-terra-firma`

- [x] 2.1 Создать `.cursor/commands/opsx-terra-firma.md` (frontmatter name/id/category/description по образцу `opsx-index.md`)
- [x] 2.2 Описать резолв change: аргумент, контекст чата, `openspec list`; если неоднозначно — спросить
- [x] 2.3 Описать предусловие: проверить наличие `tasks.md` в каталоге change файловой системой; нет → предупреждение и отказ
- [x] 2.4 Описать формирование terra.md из контекстного окна AI-агента (не поиск по репозиторию, не git)
- [x] 2.5 Описать четыре секции terra.md: Карта файлов (таблица: путь, роль, действие), Наблюдения (факты о коде), Образцы (файл-образец + что паттерн), Навигация (порядок чтения)
- [x] 2.6 Описать критерии содержания: достаточность (AI-агент не ищет файлы для выполнения задач) и необходимость (каждое утверждение устраняет cache miss)
- [x] 2.7 Включить формат terra.md (Markdown-шаблон) из design.md
- [x] 2.8 Описать запись в `<changeRoot>/terra.md`; коммит не делать

## 3. Guidance для apply

- [x] 3.1 Добавить в `operations.apply.guidance` в `openspec/config.yaml`: перед выполнением задач прочитать terra.md, если он существует в каталоге change
- [x] 3.2 Не изменять `.cursor/skills/openspec-*` и `.cursor/commands/opsx-apply.md`

## 4. Покрытие Scenario (проверка текста команды и конфига)

Каждый пункт — Scenario из `specs/terra/spec.md`. PHPUnit/Behat не применимы
(нет runtime). Done = шаг явно есть в `opsx-terra-firma.md` или
`openspec/config.yaml`.

- [x] 4.1 Scenario «Apply без terra.md» — опциональность обеспечена: guidance говорит «если существует»
- [x] 4.2 Scenario «Apply с terra.md» — guidance указывает прочитать terra.md
- [x] 4.3 Scenario «Достаточность — AI-агент не ищет файлы» — критерий в команде
- [x] 4.4 Scenario «Достаточность — AI-агент знает паттерн» — секция Образцы в команде
- [x] 4.5 Scenario «Необходимость — нет лишней информации» — критерий в команде
- [x] 4.6 Scenario «Эксперт запускает /opsx-terra-firma после tasks» — шаг в команде
- [x] 4.7 Scenario «Предупреждение без tasks.md» — шаг в команде
- [x] 4.8 Scenario «openspec validate игнорирует terra.md» — terra.md вне цепочки артефактов, CLI не знает
- [x] 4.9 Scenario «openspec status игнорирует terra.md» — terra.md вне цепочки артефактов, CLI не знает
- [x] 4.10 Scenario «Archive без terra.md» — archive не знает о terra.md, работает штатно
- [x] 4.11 Scenario «Apply не создаёт terra.md» — guidance только на чтение, не создание
- [x] 4.12 Scenario «Apply guidance упоминает terra.md» — строка в config.yaml
