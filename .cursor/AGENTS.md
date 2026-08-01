# .cursor/

Локальные инструкции Cursor для агентов этого репозитория.

| Путь | Назначение |
|------|------------|
| `skills/` | Skills (OpenSpec opsx, PHPUnit, Behat, Docker PHP, volume, …) |
| `rules/` | Always-on / glob rules (например `run-php-in-docker`) |
| `commands/` | Slash-команды (например `/adr` → файлы в корневом `adr/`) |

## Куда не класть

- Main specs и active changes OpenSpec → `openspec/`.
- Исполняемые тесты → `tests/`, `features/`.
- Операционные runbook'и (redis / dotenv / phing / volume) → `docs/`; длинные
  агентские чеклисты правок (например `add-docker-volume`) → `.cursor/skills/`.
