# E2E-приёмка (внешний клиент)

Отдельный контур для сценариев через **headless-браузер** (Symfony Panther / Chromium). Не смешивается с обычным PHPUnit Unit и «быстрым» Behat (`APP_ENV=test`, упрощённые сессии без Redis).

## Два контура

| Контур | Команда | Окружение приложения | Сессии |
| ------ | ------- | -------------------- | ------ |
| Без окружения | `make test` (suite `Unit`) | `APP_ENV=test` | не поднимаются |
| С окружением | `make test-env` (suite'ы `Integration`, `E2E`), `make behat` | `APP_ENV=test`; приложение под Panther — `APP_ENV=e2e` (`PANTHER_APP_ENV` / options). В `config/packages/framework.yaml` для `when@e2e` включён `framework.test` (test-container), **без** подмены сессий на `mock_file` | `mock_file` в `when@test`; под Panther — тот же `RedisSessionHandler`, что в **dev** и **prod** |

Harness проекта: PHPUnit suite **E2E** — открытие `/` (`tests/E2E/HomepageTest.php`); probe записи cookie-сессии в Redis — `tests/Integration/Session/E2eSessionStorageTest.php` (ключ `sf_s:`), ему нужны ядро и Redis, но не браузер. Behat — сценарии с тегом `@javascript` (`features/admin_access_*.feature`), включая вход в Sonata через `/admin/login`. HTTP-статус через Mink/Panther **не** проверяем (`getStatusCode` в драйвере недоступен); статус — в PHPUnit.

Для фикстур из Behat (ядро FoB: `APP_ENV=test`) и приложения под Panther (`APP_ENV=e2e`) в `.env.e2e` задан тот же `ENV_database_name`, что в `.env.test` (`db_name_test`). Перед прогоном Phing `-Dapp.env=test` готовит эту БД — это делает цель `test-prepare`, от которой зависят `test-env` и `behat`.

Два именованных Mink-сеанса `panther` **не** дают два независимых cookie jar: `createPantherClient()` возвращает один и тот же Client. Для двух клиентов используйте `createAdditionalPantherClient()` в PHPUnit E2E (продуктовые мультиклиентские дымы — отдельные задачи).

## Локальный запуск

Нужны поднятые контейнеры (`make up`), в том числе **Redis** (для E2E-сессий).

```bash
make test-env          # PHPUnit suite'ы Integration и E2E
make behat             # Behat целиком (браузерный срез — тег @javascript)
```

Повторный прогон той же командой должен завершаться успешно без ручных шагов.

Полный локальный QA (включая E2E): `make qa`.

## CI

В GitLab CI:

- `qa:phpunit` — `--testsuite=Unit`, без сервисов
- `qa:phpunit-env` — `--testsuite=Integration,E2E` + MySQL и Redis
- `qa:behat-env` — все сценарии + MySQL, Redis и Chromium из образа `dev`/`source`

Имя джобы — `qa:<инструмент>-<класс окружения>`; отсутствие суффикса значит,
что окружение не нужно.

Автотесты **не** гоняются против staging/prod.

Правила выбора инструмента и места теста —
[`docs/adr/0001-test-tool-and-suite-routing.md`](../adr/0001-test-tool-and-suite-routing.md).
