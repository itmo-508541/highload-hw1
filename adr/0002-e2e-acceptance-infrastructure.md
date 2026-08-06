# ADR-0002: инфраструктура E2E-приёмки (внешний клиент)

## Статус

Принято

## Дата

2026-08-06

## Контекст

В шаблоне зарезервирован третий исполняемый слой E2E (UI/browser) рядом с PHPUnit Unit и «быстрым» Behat. Нужен прогон через внешний headless-клиент локально и в GitLab CI, с cookie-сессиями как в dev/prod (Redis), без поломки ускоренного `APP_ENV=test` (`mock_file`). Автотесты не гоняются против staging/prod; платное ПО не используем. Продуктовые потоки (Admin/login и т.п.) и мультиклиентский дым изоляции/restore — вне первого внедрения; нужен инфраструктурный harness.

## Решение

Используем Symfony Panther и пакеты `chromium` / `chromium-driver` в Docker-образе `dev` (наследует CI-образ `source`) с `PANTHER_NO_SANDBOX=1` и `--disable-dev-shm-usage`. Для Behat подключаем `robertfausk/behat-panther-extension` и Mink.

Вводим отдельный `APP_ENV=e2e` для приложения под браузером (`PANTHER_APP_ENV=e2e`, `.env.e2e`). В `config/packages/framework.yaml` для `when@e2e` включаем `framework.test` (test-container) без подмены сессий на `mock_file`; handler остаётся Redis, как в dev и prod. Обычный `when@test` с `mock_file` не трогаем.

Разделяем suite: PHPUnit — `Unit` (без `tests/E2E`) и `E2E`; Behat — suite `default` (`features/`, filter `~@e2e`) и `e2e` (`features/e2e`). Команды — `make test` / `make test-e2e`, `make behat` / `make behat-e2e` (перед Behat — Phing `-Dapp.env=test`, как в CI); в CI — `qa:phpunit`, `qa:phpunit-e2e`, `qa:behat`, `qa:behat-e2e` (Redis у E2E-job’ов).

Harness первого шага: PHPUnit — открытие `/` и probe Redis `sf_s:`; Behat E2E — открытие главной без проверки HTTP status через Mink. Операционные детали — `docs/e2e.md`.

## Обоснование

Panther уже в экосистеме Symfony; Chromium в том же образе, что QA и CI, убирает расхождение local/pipeline. Отдельный `e2e` сохраняет быстрый test без Redis для сессий и одновременно даёт cookie-сессии как в prod. Два именованных Mink-сеанса `panther` не дают два независимых cookie jar; официальный путь к двум клиентам — `createAdditionalPantherClient()` в PHPUnit (для будущих продуктовых дымов).

## Рассмотренные альтернативы

- **Поднять обычный `test` на Redis «под E2E».** Ломает ускоренный контур Unit/Behat и разделение окружений.
- **Проверять HTTP-статус в Behat Mink.** `PantherDriver::getStatusCode` недоступен.
- **Playwright / Cypress / отдельный браузерный образ.** Лишняя экосистема вне стека шаблона.
- **Продуктовые сценарии Admin/login в первом change.** Вне объёма инфраструктуры; harness главной достаточен для зелёного контура.

## Последствия

- В репозитории: suite E2E, Chromium в образе, CI job’ы, `when@e2e`, runbook `docs/e2e.md`, закрытый слот в `openspec/process.md`.
- Unit и Behat default не требуют Redis ради сессий; E2E пишет сессии в Redis.
- Осознанный долг: продуктовая UI-приёмка и мультиклиентские дымы — отдельные change; Behat E2E пока harness.

## Связанные артефакты

- [`docs/e2e.md`](../docs/e2e.md) — операционный runbook двух контуров
- [`docs/redis.md`](../docs/redis.md) — сессии Redis и отличие test / E2E
- [`openspec/process.md`](../openspec/process.md) — слои тестов, включая E2E
