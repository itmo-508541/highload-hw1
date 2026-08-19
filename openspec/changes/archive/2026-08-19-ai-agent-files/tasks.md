## 1. Characterization

PHP/legacy runtime не затрагивается — characterization/regression PHPUnit и Behat
не требуются. Specs пропущены (`skip_specs: true`), Scenario в delta нет.

- [x] 1.1 Подтвердить отсутствие правок `src/`, `tests/`, `features/` в этом change

## 2. Команды → skills (`disable-model-invocation: true`)

Не использовать `/migrate-to-skills`. Frontmatter: `name` = имя каталога без `/`,
`description` из команды, `disable-model-invocation: true`; без `id`/`category`.

- [x] 2.1 Создать `.cursor/skills/adr/SKILL.md`: тело из `commands/adr.md`; вместо
      `{{{ input }}}` — хвост сообщения после `/adr`, если пусто — спросить
- [x] 2.2 Удалить `.cursor/commands/adr.md`
- [x] 2.3 Создать `.cursor/skills/opsx-index/SKILL.md`: тело из `commands/opsx-index.md`
      (алгоритм записи перенести как есть)
- [x] 2.4 Удалить `.cursor/commands/opsx-index.md`
- [x] 2.5 Создать `.cursor/skills/opsx-terra-firma/SKILL.md`: тело из
      `commands/opsx-terra-firma.md` (алгоритм перенести как есть)
- [x] 2.6 Удалить `.cursor/commands/opsx-terra-firma.md`

## 3. `vendor-class-lookup` и `add-docker-volume`

- [x] 3.1 Создать `.cursor/skills/vendor-class-lookup/SKILL.md`: тело из
      `.cursor/rules/vendor-class-lookup.mdc`; автовызов (без
      `disable-model-invocation`); `paths` не ставить; description с триггерами
      FQCN / autoload / classmap / `vendor/`
- [x] 3.2 Удалить `.cursor/rules/vendor-class-lookup.mdc`
- [x] 3.3 В `.cursor/skills/add-docker-volume/SKILL.md` добавить
      `disable-model-invocation: true`; путь скилла и `docs/volume.md` не менять

## 4. `update-rules` → `docs/AGENTS.md`

- [x] 4.1 Дописать в `docs/AGENTS.md` рецепт: контур изменился → обновить или
      добавить плоский `docs/<name>.md` (kebab-case латиницей, один контур);
      строка в таблице; при необходимости ссылка в README «Документация»
- [x] 4.2 Удалить `.cursor/skills/update-rules/`

## 5. Ссылки

- [x] 5.1 Обновить `.cursor/AGENTS.md`: `commands/` — generated `opsx-*`;
      свои slash-процедуры — skills (`adr`, `opsx-index`, `opsx-terra-firma`)
- [x] 5.2 Обновить `adr/AGENTS.md` и `adr/0000-adr-template.md` на
      `.cursor/skills/adr/SKILL.md`
- [x] 5.3 В `capability-index/topics/terra.yaml` заменить `path` и `handler` на
      `.cursor/skills/opsx-terra-firma/SKILL.md`; `entry_points.path`
      `/opsx-terra-firma` не менять; role — скилл создания terra.md
- [x] 5.4 В `capability-index/SKILL.md` заменить путь к `commands/`, если есть;
      slash `/opsx-index` оставить

## 6. Не трогать

- [x] 6.1 Не изменять `.cursor/skills/openspec-*` и remaining
      `.cursor/commands/opsx-*.md` (init)
- [x] 6.2 Не изменять `run-php-in-docker.mdc`, `adr-immutability.mdc`,
      ADR-0001, `openspec/specs/**`, `openspec/config.yaml`, `docs/volume.md`
