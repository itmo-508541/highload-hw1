## RENAMED Requirements

- FROM: `### Requirement: PHPUnit и Behat как обязательные QA-инструменты шаблона`
- TO: `### Requirement: PHPUnit и Behat как обязательные QA-инструменты проекта`

## MODIFIED Requirements

### Requirement: PHPUnit и Behat как обязательные QA-инструменты проекта
Проект MUST предоставлять конфигурацию и Makefile-цели для PHPUnit и Behat;
оба прогона (включая разделение Unit vs E2E и default vs e2e) выполняются в
Docker-сервисе `php`.

#### Scenario: Запуск слоёв через Makefile
- **WHEN** нужно прогнать unit/integration или приёмочные сценарии
- **THEN** используются `make test` (PHPUnit suite `Unit`, окружение не
  готовится) и `make test-env` (suite'ы, которым нужно окружение)
- **AND** Behat запускается целью `make behat` целиком: suite один, отбор
  срезов — тегами

### Requirement: Отдельный контур E2E с окружением e2e
Проект MUST предоставлять отдельный исполняемый контур E2E (сквозной прогон
через внешний headless-браузер), который не смешивается с обычным Unit и
«быстрой» приёмкой Behat. Приложение под браузером MUST запускаться с
окружением `e2e`, в котором cookie-сессии пишутся тем же Redis-handler’ом, что
в dev/prod, а не через упрощённое файловое хранилище `test`.

#### Scenario: E2E не подменяет сессии на mock_file
- **WHEN** выполняется проверка записи сессии в контуре E2E
- **THEN** сохранённая cookie-сессия присутствует в Redis с префиксом ключей
  сессий проекта (`sf_s:`)
- **AND** покрытие — PHPUnit в `tests/E2E/`

#### Scenario: Обычный test-контур остаётся без обязательного Redis для сессий
- **WHEN** выполняется suite Unit или Behat suite `default`
- **THEN** сессии приложения в `APP_ENV=test` используют упрощённое файловое
  хранилище (`mock_file`), и эти прогоны не требуют живого Redis ради сессий

### Requirement: Makefile и CI для E2E-контура
Проект MUST предоставлять Makefile-цели и CI-job’ы для E2E рядом с Unit и
Behat; все PHP/E2E-прогоны выполняются в Docker-сервисе `php` (образ с
браузером для E2E).

#### Scenario: Локальный запуск E2E через Makefile
- **WHEN** нужно прогнать E2E локально
- **THEN** используется `make test-env` (PHPUnit suite `E2E`) и `make behat`
  (сценарии с тегом `@javascript`), обе цели готовят test-env через Phing
- **AND** покрытие harness — PHPUnit `tests/E2E/` и Behat-сценарии
  с тегом `@javascript`

#### Scenario: CI разделяет Unit/Behat и E2E
- **WHEN** выполняется pipeline QA
- **THEN** job PHPUnit Unit не гоняет каталоги suite'ов, которым нужно
  окружение
- **AND** существуют отдельные job’ы для прогонов с окружением
  (`qa:phpunit-env`, `qa:behat-env`) с доступными MySQL и Redis
- **AND** эти job’ы входят в mainline-гейт вместе с остальным QA

### Requirement: Минимальный E2E-harness главной страницы
Проект MUST включать дымовой E2E-сценарий открытия главной страницы (`/`) во
внешнем браузере — без продуктовых потоков Admin/login.

#### Scenario: Главная открывается в браузере
- **WHEN** E2E-клиент открывает `/`
- **THEN** текущий URL заканчивается на `/`
- **AND** покрытие — PHPUnit (`tests/E2E/`) и Behat (сценарий домена
  `admin_access` с тегом `@javascript`)
