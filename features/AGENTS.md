# features/

Исполняемые приёмочные сценарии **Behat** (Gherkin). Конфиг: `behat.yml.dist`
(локально можно скопировать в `behat.yml`, не коммитить).

**Источник ожидаемого поведения** — OpenSpec specs (`openspec/specs/**` и
delta в `openspec/changes/…/specs/`). `.feature` реализует/уточняет Scenario
для приёмки; не копировать specs wholesale без нужды.

Запуск: `make behat` (Docker-сервис `php`; цель готовит test-env через Phing).
Skill: `.cursor/skills/behat-tests`. Процесс слоёв: `openspec/process.md`.

## Куда не класть

- Соглашения о поведении OpenSpec → `openspec/specs/` (**не сливать** с этим
  каталогом; не переносить `.feature` в `openspec/`).
- Unit/integration PHPUnit → `tests/`.
- Будущий E2E (UI/browser) → отдельный слой (TBD), не сюда и не в `openspec/`.
- Cursor skills / process docs → `.cursor/skills/`, `openspec/process.md`.
