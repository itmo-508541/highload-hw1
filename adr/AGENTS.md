# adr/

Долгоживущие Architecture Decision Records (ADR) шаблона.

| Путь | Назначение |
|------|------------|
| `0000-adr-template.md` | Шаблон и ADR о ведении ADR |
| `NNNN-<slug>.md` | Принятые / предложенные решения |

## Правила

- Создавать новые ADR скиллом Cursor `/adr` (см. `.cursor/skills/adr/SKILL.md`).
- Принятые ADR иммутабельны (кроме раздела «Статус») —
  `.cursor/rules/adr-immutability.mdc`.
- Связь с OpenSpec change: после `design.md` / до или в `tasks.md`, только при
  значимом архитектурном решении (см. `.cursor/skills/adr/SKILL.md`).
- Не класть сюда delta specs, runbook'и или исполняемые тесты.
