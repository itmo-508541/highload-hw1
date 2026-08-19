# .cursor/

Локальные инструкции Cursor для агентов этого репозитория.

| Путь | Назначение |
|------|------------|
| `skills/` | Skills: PHPUnit, Behat, Docker PHP, volume; свои slash-процедуры `/adr`, `/opsx-index`, `/opsx-terra-firma` (`disable-model-invocation`); generated `openspec-*` |
| `rules/` | Always-on / glob rules (например `run-php-in-docker`, `adr-immutability`) |
| `commands/` | Generated OpenSpec slash-команды (`opsx-apply`, `opsx-new`, … из `openspec init`) |

## Куда не класть

- Main specs и active changes OpenSpec → `openspec/`.
- Исполняемые тесты → `tests/`, `features/`.
- Операционные runbook'и (redis / dotenv / phing / volume) → `docs/`; длинные
  агентские чеклисты правок (например `add-docker-volume`) → `.cursor/skills/`.
