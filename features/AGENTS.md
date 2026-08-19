# features/

Исполняемые приёмочные сценарии **Behat** (Gherkin) и контексты. Конфиг:
`behat.yml.dist` (локально можно скопировать в `behat.yml`, не коммитить).
Контексты: `features/bootstrap/`, namespace `App\Behat\`. Фикстуры контекста —
`./fixtures/` относительно `*Context.php`. Шаг, указывающий каталог фикстуры,
задаёт путь относительно `features/bootstrap/` (например `fixtures/<dir>`).

**test env autoload** — `tests/bootstrap.php` (FoB `bootstrap:`), не каталог
контекстов.

**Источник ожидаемого поведения** — OpenSpec specs (`openspec/specs/**` и
delta в `openspec/changes/…/specs/`). `.feature` реализует/уточняет Scenario
для приёмки; не копировать specs wholesale без нужды.

Запуск: `make behat` (suite `default`, `APP_ENV=test`); E2E — `make behat-e2e`
(suite `e2e`, сценарии в `features/e2e/`). Docker-сервис `php`.
Skill: `.cursor/skills/behat-tests`. E2E runbook: `docs/e2e.md`.

## Куда не класть

- Соглашения о поведении OpenSpec → `openspec/specs/` (**не сливать** с этим
  каталогом; не переносить `.feature` в `openspec/`).
- Unit/integration PHPUnit → `tests/` (не `tests/E2E/` для «быстрой» приёмки).
- E2E Behat — только в `features/e2e/` (не смешивать с suite `default` в корне
  `features/`); PHPUnit E2E → `tests/E2E/`. Не в `openspec/`.
- Cursor skills → `.cursor/skills/`.
