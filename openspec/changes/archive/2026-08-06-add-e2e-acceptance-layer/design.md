## Context

См. proposal.md — Why. Сейчас в шаблоне есть Unit (`make test`) и Behat
(`make behat`, Phing test-env), Redis-сессии в dev/prod и `mock_file` в
`when@test`, главная `/` (`IndexController`). Слот E2E в process — TBD.
Переносим уже принятый каркас из Calculator/OpenSpec-calculator (Panther +
Chromium в образе `dev`/`source`), без продуктовых сценариев.

## Goals / Non-Goals

**Goals:**

- Два изолированных контура: быстрый `test` и браузерный `e2e` с Redis-сессиями.
- Единый local/CI путь: Chromium в том же PHP-образе, Makefile + GitLab jobs.
- Минимальный зелёный harness (`/` + probe `sf_s:`), закрытие слота в process/docs.
- Durable ADR о выборе инфраструктуры E2E.

**Non-Goals (design-level):**

- Мультиклиентский дым изоляции/restore продуктового состояния.
- Отдельный browser-сервис в compose / Playwright.
- Расширение Behat suite `default` браузерными сценариями.

## Decisions

1. **Стек: Symfony Panther + Chromium в образе `dev`**
   - `PANTHER_NO_SANDBOX=1`, `--disable-dev-shm-usage`.
   - Behat: `robertfausk/behat-panther-extension` + Mink (без опоры на
     `mink-browserkit-driver` для E2E).
   - *Альтернативы:* Playwright/Cypress (лишняя экосистема); отдельный
     chrome-сервис (расхождение local/CI). Отклонены как в ADR родственных репо.

2. **Окружение `APP_ENV=e2e`**
   - `.env.e2e` + `PANTHER_APP_ENV=e2e`.
   - `when@e2e`: `framework.test: true`, **без** `mock_file`; handler Redis
     как в default framework config.
   - `when@test` с `mock_file` не трогаем.

3. **Разделение suite, не новый top-level runner**
   - PHPUnit: `Unit` (exclude `tests/E2E`) и `E2E`.
   - Behat: `default` (`features/`, filter `~@e2e`) и `e2e` (`features/e2e/`).
   - Это и есть «третий слой» process: отдельные каталоги/suite/команды, не
     слияние с OpenSpec specs.

4. **Makefile / Phing**
   - `make test` → `--testsuite=Unit`; `make test-e2e` → `E2E`.
   - `make behat` / `make behat-e2e`: сохранить Phing `-Dapp.env=test` перед
     Behat (как сейчас у `behat` в шаблоне); suite явно `default` / `e2e`.
   - `make qa` = существующие проверки + `test-e2e` + `behat-e2e`.

5. **CI**
   - `qa:phpunit` → `--testsuite=Unit` (без Redis ради сессий).
   - `qa:phpunit-e2e` / `qa:behat-e2e` + service Redis; образ уже с Chromium.
   - `qa:behat` оставить с текущими services шаблона (MySQL/Redis), suite
     `default`.
   - Подключить E2E-job’ы в `needs` mainline-гейта.

6. **Harness первого PR**
   - PHPUnit: `HomepageTest` (URL `/`, HTTP 200) + `E2eSessionStorageTest`
     (Redis key `sf_s:`).
   - Behat: `features/e2e/homepage.feature` + `HomepageContext` (URL ends with `/`;
     **не** проверять status code через Mink/Panther — драйвер не отдаёт).
   - Без `/admin/login` и без `SessionIsolationRestoreTest`.

7. **Документы и ADR**
   - Runbook `docs/e2e.md`; запись в `docs/AGENTS.md`.
   - Закрыть TBD в `openspec/process.md` и `openspec/config.yaml` по образцу
     OpenSpec-calculator; обновить `features/AGENTS.md`, `tests/AGENTS.md`,
     skill `run-php-in-docker` (+ при необходимости phpunit/behat skills).
   - **Durable ADR обязателен** (`/adr` → `adr/NNNN-e2e-acceptance-infrastructure.md`):
     выбор Panther/Chromium, `APP_ENV=e2e`, suite-разделение, границы harness.

## Risks / Trade-offs

- **[Risk] Chrome в Docker без sandbox падает** → `PANTHER_NO_SANDBOX=1` и
  `--disable-dev-shm-usage` в образе (проверено в родственных репо).
- **[Risk] Два Mink `panther` ≠ два cookie jar** → мультиклиентские дымы только
  через PHPUnit `createAdditionalPantherClient` (вне scope этого change).
- **[Risk] CI image без Chromium** → ставить пакеты в stage `dev` (наследует
  `source`/QA image), не в slim `prod`.
- **[Trade-off] Behat E2E не проверяет HTTP status** → допустимо; статус — в
  PHPUnit `HomepageTest`.
- **[Trade-off] `make qa` становится тяжелее** → ожидаемо; Unit/default остаются
  быстрым локальным контуром по отдельности.

## Migration Plan

1. Composer require-dev + lock; Dockerfile Chromium; env/config/suites.
2. Harness-тесты; Makefile; CI jobs + gate.
3. Docs/ADR/process/AGENTS/skills; delta specs уже в change.
4. Проверка: `make test`, `make behat`, `make test-e2e`, `make behat-e2e`
   (при широкой сдаче — `make qa`).
5. Rollback: revert change / убрать E2E jobs из gate; Unit/Behat default не
   зависят от Chromium.

## Open Questions

Нет блокирующих: `/admin/login` исключён; стек и harness согласованы с двумя
предыдущими внедрениями.
