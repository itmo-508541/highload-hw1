## Why

В процессе шаблона зарезервирован третий исполняемый слой E2E (UI/browser), но
он не реализован: нет suite, Chromium в образе, Makefile/CI-контура и runbook.
Нужно закрыть follow-up F-2, перенеся уже дважды отработанную инфраструктуру
Panther из родственных проектов — без продуктовых сценариев (Admin/login и т.п.).

## What Changes

- Ввести окружение `APP_ENV=e2e` и конфиг `when@e2e`: test-container без
  подмены сессий на `mock_file` (Redis как в dev/prod).
- Добавить зависимости Panther / Behat-Panther / Mink; Chromium и
  chromium-driver в Docker-образ `dev` (наследует CI `source`).
- Разделить PHPUnit на suite `Unit` и `E2E` (`tests/E2E/`); Behat — suite
  `default` и `e2e` (`features/e2e/`).
- Makefile: `make test` → Unit, `make test-e2e`, `make behat` → default,
  `make behat-e2e`; `make qa` включает E2E.
- GitLab CI: `qa:phpunit-e2e`, `qa:behat-e2e` (с Redis); сузить
  `qa:phpunit` до Unit; подключить E2E-job’ы к mainline-гейту.
- Минимальный harness: открытие `/` (PHPUnit + Behat) и probe записи
  cookie-сессии в Redis (`sf_s:`).
- Документация: `docs/e2e.md`, ADR, закрытие слота E2E в `process.md` /
  `config.yaml` / AGENTS / skills; обновление main spec `qa` (и при
  необходимости `platform`).

## Non-goals

- Сценарии `/admin/login`, JWT-auth, Sonata и прочая продуктовая приёмка UI.
- Дым изоляции/restore двух клиентов с калькуляторной логикой
  (`SessionIsolationRestoreTest` из других репозиториев).
- Playwright / Cypress / отдельный browser-образ.
- Формальная traceability OpenSpec ↔ тесты (F-1).
- Прогон E2E против staging/prod.

## Capabilities

### New Capabilities

_(нет — поведение описывается как расширение существующих слоёв QA/platform)_

### Modified Capabilities

- `qa`: третий слой E2E (каталоги/suite, команды, отличие `test` vs `e2e`
  по сессиям); обязательные Makefile/CI-контуры для E2E рядом с Unit/Behat.
- `platform`: фасад Makefile и Docker-only QA включают цели E2E
  (`test-e2e` / `behat-e2e`) без запуска PHP на хосте.

## Impact

- **Код / конфиг:** `framework.yaml` (`when@e2e`), `.env.e2e` / `.env.test`,
  `phpunit.dist.xml`, `behat.yml.dist`, `Dockerfile` (Chromium),
  `composer.json` (+ lock), `.gitignore` (screenshots).
- **Тесты:** `tests/E2E/*`, `features/e2e/*`, `tests/Behat/HomepageContext.php`.
- **Ops / CI:** `.gitlab-ci.yml` (новые job’ы, needs гейта), Makefile, `make qa`.
- **Документы / процесс:** `docs/e2e.md`, ADR, `openspec/process.md`,
  `openspec/config.yaml`, AGENTS (`docs/`, `features/`, `tests/`), skills
  (`run-php-in-docker`, при необходимости phpunit/behat).
- **API:** без изменений контрактов REST/Admin.
