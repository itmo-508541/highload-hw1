## 1. Зависимости и образ

- [x] 1.1 Добавить require-dev: `symfony/panther`, `robertfausk/behat-panther-extension`, `friends-of-behat/mink-extension` (через Composer в Docker-сервисе `php`); обновить `composer.lock` / `symfony.lock`
- [x] 1.2 В `Dockerfile` stage `dev`: установить `chromium` и `chromium-driver`; задать `PANTHER_NO_SANDBOX=1` и `PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'`
- [x] 1.3 Пересобрать/поднять dev-образ (`make up` / compose build по принятому в репо способу), чтобы Chromium был доступен в сервисе `php`

## 2. Окружение и конфиг приложения

- [x] 2.1 Добавить `.env.e2e` с переменными Panther (`PANTHER_APP_ENV=e2e`, screenshot dir, `PANTHER_DEVTOOLS=0`)
- [x] 2.2 Дополнить `.env.test` блоком Panther (как в родственных репо)
- [x] 2.3 В `config/packages/framework.yaml` добавить `when@e2e` с `framework.test: true` без `mock_file`; не менять `when@test`
- [x] 2.4 Добавить `/var/error-screenshots` в `.gitignore`

## 3. Suite PHPUnit и Behat

- [x] 3.1 Обновить `phpunit.dist.xml`: suite `Unit` (exclude `tests/E2E`), suite `E2E`; bootstrap extension `Symfony\Component\Panther\ServerExtension`
- [x] 3.2 Обновить `behat.yml.dist`: suite `default` (paths `features`, filter `~@e2e`, текущие contexts); suite `e2e` (paths `features/e2e`, `HomepageContext`); расширения Panther + Mink (`APP_ENV=e2e` в options сессии)

## 4. Harness-тесты (Scenario → тест)

- [x] 4.1 PHPUnit `tests/E2E/HomepageTest.php` — Scenario «Главная открывается в браузере» (URL ends with `/`, status 200)
- [x] 4.2 PHPUnit `tests/E2E/E2eSessionStorageTest.php` — Scenario «E2E не подменяет сессии на mock_file» (ключ Redis `sf_s:`)
- [x] 4.3 Behat `features/e2e/homepage.feature` + `tests/Behat/HomepageContext.php` — Scenario «Главная открывается в браузере» (без проверки HTTP status через Mink)
- [x] 4.4 Убедиться, что существующие Unit/Behat default по-прежнему зелёные и не тянут `tests/E2E` / `features/e2e` (Scenario «Обычный test-контур…», «Расположение артефактов…»)

## 5. Makefile и CI

- [x] 5.1 Makefile: `make test` → `--testsuite=Unit`; добавить `test-e2e`, `behat` с `--suite=default`, `behat-e2e` с Phing `-Dapp.env=test` + `--suite=e2e`; включить E2E в `make qa` (Scenario «Локальный запуск E2E…», platform «Запуск E2E…» / «Первый запуск и ежедневный QA»)
- [x] 5.2 `.gitlab-ci.yml`: `qa:phpunit` → `--testsuite=Unit`; добавить `qa:phpunit-e2e` и `qa:behat-e2e` с Redis; `qa:behat` → `--suite=default`; добавить E2E-job’ы в `needs` mainline-гейта (Scenario «CI разделяет Unit/Behat и E2E»)
- [x] 5.3 Обновить skill `.cursor/skills/run-php-in-docker` (и при необходимости phpunit/behat skills) целями `test-e2e` / `behat-e2e`

## 6. Документация, ADR, process

- [x] 6.1 Создать `docs/e2e.md` (два контура, команды, CI); строка в `docs/AGENTS.md`
- [x] 6.2 ADR через `/adr` → `adr/NNNN-e2e-acceptance-infrastructure.md` (Panther/Chromium, `APP_ENV=e2e`, suite, границы harness)
- [x] 6.3 Закрыть TBD E2E в `openspec/process.md` и `openspec/config.yaml` (слой = `tests/E2E` + `features/e2e`, команды Makefile)
- [x] 6.4 Обновить `features/AGENTS.md` и `tests/AGENTS.md` (куда класть E2E vs default/Unit)

## 7. Проверка

- [x] 7.1 `make test` и `make behat` — зелёные (Unit + default)
- [x] 7.2 `make test-e2e` и `make behat-e2e` — зелёные (нужен Redis в compose)
- [x] 7.3 При широкой сдаче — `make qa`
