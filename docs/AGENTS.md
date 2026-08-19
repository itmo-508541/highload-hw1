# docs/

Только **операционные runbook'и**: как проверить, настроить или прогнать
конкретный контур руками (команды, smoke, ловушки).

| Путь | Назначение |
|------|------------|
| `redis.md` | Redis: сессии, smoke `redis-cli`, local vs prod AUTH |
| `e2e.md` | E2E: Panther/Chromium, контуры Unit vs E2E, CI job’ы |
| `dotenv.md` | Dotenv: цепочка env, prod-шим, группы `ENV_*` |
| `phing.md` | Phing: `make init`, bootstrap/`symfonyenv`, test-env для Behat |
| `volume.md` | Именованный bind-volume: когда нужен, smoke, ловушки (+ skill) |

## Куда не класть

- Поведение продукта/шаблона (Requirement / Scenario) → `openspec/specs/`.
- Архитектурные решения («почему») → корневой `adr/` (команда `/adr`).
- Быстрый старт и принципы разработки → `README.md`.
- Деплой / CI-гейт → `DEPLOYMENT.md` + `.gitlab-ci.yml`.
- Длинные агентские чеклисты с правками compose/CI → `.cursor/skills/`
  (пример: `add-docker-volume`; runbook `volume.md` — smoke и контекст, не
  полный дубль skill).
- Исполняемые тесты → `tests/`, `features/`.
- Обзоры стека / каталоги бандлов — не сюда (README / ABOUT; краткий стек в
  `openspec/config.yaml` context).
