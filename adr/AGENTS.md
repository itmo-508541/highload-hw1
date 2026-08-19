# adr/

Долгоживущие Architecture Decision Records (ADR) шаблона.

| Путь | Назначение |
|------|------------|
| `0000-adr-template.md` | Шаблон и ADR о ведении ADR |
| `NNNN-<slug>.md` | Принятые / предложенные решения |

## Правила

- Создавать новые ADR командой Cursor `/adr` (см. `.cursor/commands/adr.md`).
- Принятые ADR иммутабельны (кроме раздела «Статус») —
  `.cursor/rules/adr-immutability.mdc`.
- Связь с OpenSpec change: после `design.md` / до или в `tasks.md`, только при
  значимом архитектурном решении (см. `.cursor/commands/adr.md`).
- Не класть сюда delta specs, runbook'и или исполняемые тесты.
