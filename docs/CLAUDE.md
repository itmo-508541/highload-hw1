# docs/

Два жителя, не смешивать.

- `docs/adr/` — Architecture Decision Records: **почему** так решено.
- `docs/runbooks/` — операционные runbook'и: как проверить, настроить или прогнать
  конкретный контур руками (команды, smoke, ловушки).

| Путь | Назначение |
|------|------------|
| `runbooks/redis.md` | Redis: сессии, smoke `redis-cli`, local vs prod AUTH |
| `runbooks/e2e.md` | E2E: Panther/Chromium, контуры Unit vs E2E, CI job’ы |
| `runbooks/dotenv.md` | Dotenv: цепочка env, prod-шим, группы `ENV_*` |
| `runbooks/phing.md` | Phing: `make init`, bootstrap/`symfonyenv`, test-env для Behat |
| `runbooks/volume.md` | Именованный bind-volume: когда нужен, smoke, ловушки |

## Новый или изменённый контур

Изменение вводит или меняет операционный контур — обновить существующий
`docs/runbooks/<name>.md` или добавить плоский файл `docs/runbooks/<name>.md`
(kebab-case латиницей, один контур на файл). После добавления — строка в таблице выше.

## ADR

`docs/adr/NNNN-<slug>.md`, нумерация сквозная. ADR пишется на решение, которое
дорого отменить и за которым стоит настоящий трейдоф; решения по отдельной
задаче живут в артефактах своего OpenSpec change, а не здесь.

## Куда не класть

- Поведение продукта (Requirement / Scenario) → `openspec/specs/`.
- Быстрый старт и принципы разработки → `README.md`.
- Деплой / CI-гейт → `DEPLOYMENT.md` + `.gitlab-ci.yml`.
- Пошаговые чеклисты для агента → `.claude/skills/`.
- Исполняемые тесты → `tests/`, `features/`.
- Обзоры стека и каталоги бандлов → `README.md` / `ABOUT.md`.
