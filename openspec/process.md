# Процесс изменений (OpenSpec)

Единый процесс команды: от идеи до принятого поведения в `openspec/specs/`.
OpenSpec — прежде всего **процесс и артефакты в git**; CLI помогает, но не
заменяет договорённости.

## Профиль и инструменты

- Профиль OpenSpec: **`core`** (explore → propose → apply → archive).
  Expanded workflows и `/opsx-onboard` **не** используем, пока команда явно
  не решит расширить профиль.
- Схема по умолчанию: `spec-driven` (`openspec/config.yaml`).
- Cursor: `/opsx-explore`, `/opsx-propose`, `/opsx-apply`, `/opsx-archive`
  (и связанные skills `openspec-*`).
- CLI локально: опционален. Если нужен — через **`npx`** из корня репозитория
  после `npm ci` (без глобальной установки и без Makefile-таргета):

  ```bash
  npm ci
  npx openspec --version
  npx openspec validate --all --no-interactive
  npx openspec list
  ```

- CLI в CI: **обязателен** — job `qa:openspec` (см. `.gitlab-ci.yml`).

## Роли

| Роль | Что делает | Типичные команды / артефакты |
|------|------------|------------------------------|
| Аналитик / product | Формулирует проблему и желаемое поведение | `/opsx-explore` (по необходимости) → `/opsx-propose` → ревью `proposal.md`, delta `specs/`, `design.md`, `tasks.md` |
| Разработчик | Реализует утверждённый change | `/opsx-apply` по `tasks.md`; PHPUnit/Behat по scope; при архитектурном решении — ADR (`/adr` → `adr/`) |
| Тестировщик / QA | Сверяет сценарии specs с исполняемыми тестами | Читает `openspec/changes/…/specs` и main `openspec/specs/`; дополняет `features/` и `tests/`; `make test` / `make behat` |
| Ревьюер / архивариус | Проверяет готовность и закрывает change | Ревью MR + артефактов; `/opsx-archive` после зелёных проверок |

Один человек может совмещать роли; важен порядок артефактов, не должности.

## Жизненный цикл change

```text
(пусто / нет активных changes)
        │
        ▼
  /opsx-explore     ← опционально: разобрать неизвестность
        │
        ▼
  /opsx-propose     → openspec/changes/<name>/
                      proposal.md, specs/**/spec.md, design.md, tasks.md
        │
        ▼
  ревью команды     ← утверждение «что» и «как» до кода
        │
        ▼
  /opsx-apply       → код + тесты по tasks.md
        │
        ▼
  QA / CI           → make test / make behat (Docker); qa:openspec
        │
        ▼
  /opsx-archive     → delta specs → openspec/specs/; change в archive/
```

### Первый change (пустой список)

Если `openspec/changes/` пуст и main specs ещё тонкие или отсутствуют —

1. Сформулируйте изменение (имя kebab-case, например `add-healthcheck`).
2. Запустите `/opsx-propose` (или CLI `npx openspec new change …` + генерация
   артефактов). Не ждите «готового» каталога specs — brownfield растёт от
   реальных changes.
3. После ревью — `/opsx-apply`, проверки, `/opsx-archive`.

`npx openspec list` без активных changes — нормальное стартовое состояние.

## Артефакты

| Артефакт | Назначение |
|----------|------------|
| `proposal.md` | Зачем и что меняем; non-goals; impact |
| `specs/<capability>/spec.md` | Delta требований и сценариев (соглашение о поведении) |
| `design.md` | Как реализуем; риски; точки интеграции |
| `tasks.md` | Чеклист реализации и проверок |

Язык артефактов команды: **русский**. ID, пути, имена CLI и capability —
как принято в инструментах (латиница/kebab-case где нужно).

Канон поведения после archive — **main specs** в `openspec/specs/`, не
разрозненные заметки и не отдельный changelog-файл.

## Куда класть документацию

Модель **по роли документа**, не по «уровням / этапам / шагам»:

| Нужно | Куда |
|-------|------|
| Продукт: зачем система, сценарии, стек | [`ABOUT.md`](../ABOUT.md) |
| Разработка: быстрый старт, принципы | [`README.md`](../README.md) |
| Деплой: гейт, переменные, обзор пайплайна | [`DEPLOYMENT.md`](../DEPLOYMENT.md) → детали в `.gitlab-ci.yml` |
| Процесс изменений, роли, ADR, слои тестов | этот файл (`process.md`) |
| Поведение (Requirement / Scenario) | `openspec/specs/` (через change → archive) |
| Операционные runbook'и («как сделать») | `docs/` (`redis`, `e2e`, `dotenv`, `phing`, `volume`) и/или `.cursor/skills/` |
| Архитектурные решения | корневой [`adr/`](../adr/) (`/adr`) |
| История коммитов | **git**; принятое поведение — specs + `changes/archive/` |

Правила:

- Один источник правды для длинной процедуры — один файл в `docs/` (или skill);
  корневые входы дают краткий текст и **ссылку**.
- Имена файлов и классов в документации — **как в коде**.
- Заглушки продукта в `ABOUT.md` помечать явно, пока нет контракта в specs.
- **Не** вести `CHANGELOG.md`.

## Слои тестов (не смешивать каталоги)

| Слой | Где | Роль |
|------|-----|------|
| Соглашение о поведении | `openspec/specs/**`, delta в change | Requirement + Scenario |
| Unit / integration | `tests/` + PHPUnit (`make test`) | Код и контракты |
| Приёмка (BDD) | `features/` + Behat (`make behat`) | Исполняемые сценарии |
| E2E (браузер) | `tests/E2E/` + PHPUnit suite E2E; `features/e2e/` + Behat suite e2e | Panther / Chromium; `make test-e2e` / `make behat-e2e`; runbook `docs/e2e.md` |

**Запрещено** сливать `openspec/specs/**` и `features/`: не переносить
`.feature` в OpenSpec и не класть `spec.md` в `features/`. Skills:
`.cursor/skills/phpunit-tests`, `.cursor/skills/behat-tests`.

Связь слоёв — ссылками в `tasks.md` / specs / skills, **не** общим каталогом
и **не** формальной матрицей ID (traceability — follow-up вне текущего процесса).
PHP и QA-инструменты — **только** в Docker-сервисе `php` (см. `README.md`,
`.cursor/rules/run-php-in-docker.mdc`).

### Куда класть новое

| Нужно зафиксировать… | Куда |
|----------------------|------|
| Ожидаемое поведение (Requirement / Scenario) | delta specs в change → после archive в `openspec/specs/` |
| Проверка кода / контракта без UI-потока | `tests/` + PHPUnit; часто **достаточно одного** этого слоя |
| Исполняемую приёмку (Gherkin) | `features/*.feature` + контексты в `features/bootstrap/` (`App\Behat\`, suite `default`; не `features/e2e/`); фикстуры Behat — `features/bootstrap/fixtures/` |
| Сквозной UI/browser E2E | `tests/E2E/` (PHPUnit suite E2E) и/или `features/e2e/` (Behat suite e2e); детали — `docs/e2e.md`. Не класть в `openspec/specs/` и не смешивать с «быстрой» приёмкой `features/` + `APP_ENV=test` |

`tests/` — только PHPUnit (`*Test.php`, фикстуры рядом с тестом в `./fixtures/`)
и общий файл **test env autoload** `tests/bootstrap.php` (его же читает FoB).
Контексты Behat в `tests/` не класть. `features/` — только Behat.

Каждый Scenario из **затрагиваемого** spec должен иметь или получить
подтверждение тестом: PHPUnit и/или Behat по смыслу Scenario (не обязательно
оба слоя на каждый Scenario).

## Когда писать ADR

**Механизм (Alt-A):** команда Cursor `/adr` → долгоживущие файлы в корневом
[`adr/`](../adr/). Схема OpenSpec остаётся stock `spec-driven` (отдельного
артефакта `adr` в change **нет**). ADR **не обязателен** для каждого change.

Создавайте ADR, если change фиксирует значимое архитектурное или
инфраструктурное решение (выбор технологии, граница модуля, модель данных,
контракт деплоя и т.п.) — см. [ADR-0001](../adr/0001-adr-via-cursor-command-under-openspec.md).

### Точка в жизненном цикле change

```text
proposal → specs → design → [ADR при нужде] → tasks → apply → archive
                              │
                              └─ /adr → adr/NNNN-<slug>.md
                                 + задача в tasks.md
```

1. В `design.md` зафиксируйте, нужно ли ADR (риски, границы, альтернативы).
2. Если да — создайте ADR командой `/adr` **после** design и **до или во время**
   составления `tasks.md` (пока альтернативы ещё сравнимы).
3. В `tasks.md` добавьте явный пункт на создание/принятие ADR и ссылку на файл.
4. Если архитектурного решения нет — не изобретайте ADR; в design достаточно
   кратко отметить, что durable ADR не требуется.

Иммутабельность принятых ADR: `.cursor/rules/adr-immutability.mdc` (glob
`adr/*.md`).

## Проверки

- Локально по затронутому scope: `make test`, `make behat` (в Docker); при
  E2E-scope — `make test-e2e` / `make behat-e2e`; при широком изменении —
  `make qa`.
- OpenSpec: `npx openspec validate --all --no-interactive`.
- CI: PHP QA jobs (в т.ч. `qa:phpunit-e2e` / `qa:behat-e2e`) + `qa:openspec`
  до mainline-гейта.

При закрытии change (`/opsx-archive`) достаточно краткого summary исхода.
**Не** создавать и **не** дополнять `CHANGELOG.md`.

## Правила для артефактов в `openspec/`

- Не ссылаться на временные постановки вне процесса OpenSpec.
- Не дублировать длинные runbook'и из `docs/` в specs: поведение — в specs,
  операционные рецепты — в docs/skills со ссылками.
- Контекст стека и правила генерации — в `openspec/config.yaml`.
- Краткие маркеры «роль каталога / куда не класть» — `AGENTS.md` в
  `openspec/`, `features/`, `tests/`, `docs/`, `.cursor/`.

## Skills (рецепты для агентов)

| Задача | Skill |
|--------|-------|
| PHP/QA только в Docker | `.cursor/skills/run-php-in-docker` |
| PHPUnit | `.cursor/skills/phpunit-tests` |
| Behat | `.cursor/skills/behat-tests` |
| Новый именованный Docker volume | `.cursor/skills/add-docker-volume` |
| OpenSpec explore / propose / apply / archive | `.cursor/skills/openspec-*` |

## Быстрые ссылки

- Конфиг: [`config.yaml`](./config.yaml)
- Specs: [`specs/`](./specs/) (начальные: `platform`, `qa`)
- Changes: [`changes/`](./changes/)
- ADR (durable): [`../adr/`](../adr/)
- Операционные runbook'и: [`../docs/`](../docs/) (`redis`, `e2e`, `dotenv`, `phing`, `volume`)
- Маркер каталога: [`AGENTS.md`](./AGENTS.md)
- Вход в репозиторий: [`../README.md`](../README.md)
