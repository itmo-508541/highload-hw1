# features/

Behat приёмочные сценарии (Gherkin) и контексты.

## Конфигурация

- `behat.yml.dist` (локально: копия → `behat.yml`, не коммитить).
- Bootstrap: `tests/bootstrap.php` (FoB `bootstrap:`).
- Расширение: `FriendsOfBehat\SymfonyExtension`, ядро в `test`.
- Suite `default` (`features/`, filter `~@e2e`), suite `e2e` (`features/e2e/`,
  Mink + Panther).

## Конвенции

- Контексты: `features/bootstrap/`, namespace `App\Behat\`.
- Фикстуры: `./fixtures/` относительно `*Context.php`. Путь в шагах —
  относительно `features/bootstrap/` (например `fixtures/<dir>`).
- Стиль шагов — атрибуты `#[When]` / `#[Then]` (как в соседних контекстах).
- Новый контекст → зарегистрировать в `behat.yml.dist`.
- При наличии активного OpenSpec change — specs задают ожидаемое поведение;
  `.feature` реализует/уточняет Scenario, не дублирует wholesale.

## Команды

```bash
make behat                            # suite default
make behat-e2e                        # suite e2e (нужен Redis)
make behat features/foo.feature       # один файл
```

Skill: `.cursor/skills/behat-tests`. E2E runbook: `docs/e2e.md`.

## Куда не класть

- OpenSpec specs → `openspec/` (не сливать с `features/`)
- Unit/integration PHPUnit → `tests/`
- E2E Behat — только `features/e2e/`, не смешивать с suite `default`
