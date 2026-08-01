# openspec/

Процесс изменений команды (OpenSpec): артефакты в git важнее CLI.

| Путь | Назначение |
|------|------------|
| `process.md` | Единый процесс: роли, lifecycle, docs-модель, слои тестов, ADR |
| `config.yaml` | Context стека + rules для генерации артефактов |
| `specs/` | Main specs — канон принятого поведения |
| `changes/` | Активные changes; `changes/archive/` — закрытые |

Долгоживущие ADR — **не** здесь: корневой [`../adr/`](../adr/) (команда `/adr`).

## Куда не класть

- Исполняемые Behat `.feature` → `features/` (не сюда).
- PHPUnit-тесты → `tests/`.
- Операционные runbook'и → `docs/` (`redis`, `dotenv`, `phing`, `volume`) или `.cursor/skills/`.
- Durable ADR → `adr/` (не `openspec/adr/`).
- Временные постановки вне OpenSpec — не ссылаться из артефактов этого каталога.

CLI: `npx openspec …` после `npm ci`. Подробности — `process.md`.
