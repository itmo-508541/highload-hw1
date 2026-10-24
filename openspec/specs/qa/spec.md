## Purpose

Соглашение о слоях проверки качества в проекте: где живут unit/приёмочные
сценарии и что они не смешиваются с OpenSpec specs.

## Requirements

### Requirement: Раздельные каталоги для слоёв тестов и specs
Соглашение о поведении (OpenSpec), unit/integration (PHPUnit), приёмка (Behat)
и E2E (браузерный контур) MUST храниться в разных каталогах/suite и не сливаться
в один с OpenSpec specs. Каталог `features/` MUST содержать только артефакты
Behat (Gherkin, контексты, фикстуры Behat). Каталог `tests/` MUST содержать
только артефакты PHPUnit (`*Test.php`, фикстуры PHPUnit) и общий файл test env
autoload. Контексты Behat MUST жить в `features/bootstrap/` и MUST NOT жить
под `tests/`.

#### Scenario: Расположение артефактов по слоям
- **WHEN** команда фиксирует ожидаемое поведение, unit-тест, приёмочный сценарий
  или E2E-сценарий
- **THEN** поведение описывается в `openspec/specs/**` (или delta в change)
- **AND** PHPUnit-тесты лежат в каталоге suite, выбранного по точке
  наблюдения эффекта (`tests/Unit/`, `tests/Integration/`, `tests/E2E/`)
- **AND** исполняемые Behat-сценарии остаются в `features/`, один suite,
  браузерный срез режется тегом `@javascript`
- **AND** контексты Behat остаются в `features/bootstrap/` (не в `tests/`)
- **AND** каталоги `openspec/specs` и `features` не объединяются

#### Scenario: Фикстуры рядом с владельцем
- **WHEN** команда добавляет фикстуру для PHPUnit-теста или Behat-контекста
- **THEN** файлы лежат в `fixtures/` относительно `*Test.php` или
  `*Context.php`, который ими пользуется
- **AND** шаг Gherkin, указывающий каталог фикстуры Behat, задаёт путь
  относительно `features/bootstrap/` (например `fixtures/<dir>`)
- **AND** общий каталог `tests/tools/` не используется

#### Scenario: test env autoload не подменяет каталог контекстов
- **WHEN** тестовый процесс PHPUnit или Behat загружает окружение `APP_ENV=test`
- **THEN** используется файл `tests/bootstrap.php` как test env autoload
- **AND** в `tests/` нет классов контекстов Behat

### Requirement: PHPUnit и Behat как обязательные QA-инструменты шаблона
Шаблон MUST предоставлять конфигурацию и Makefile-цели для PHPUnit и Behat;
оба прогона (включая разделение Unit vs E2E и default vs e2e) выполняются в
Docker-сервисе `php`.

#### Scenario: Запуск слоёв через Makefile
- **WHEN** нужно прогнать unit/integration или приёмочные сценарии
- **THEN** используются `make test` (PHPUnit suite `Unit`, окружение не
  готовится) и `make test-env` (suite'ы, которым нужно окружение)
- **AND** Behat запускается целью `make behat` целиком: suite один, отбор
  срезов — тегами

### Requirement: Отдельный контур E2E с окружением e2e
Шаблон MUST предоставлять отдельный исполняемый контур E2E (сквозной прогон
через внешний headless-браузер), который не смешивается с обычным Unit и
«быстрой» приёмкой Behat. Приложение под браузером MUST запускаться с
окружением `e2e`, в котором cookie-сессии пишутся тем же Redis-handler’ом, что
в dev/prod, а не через упрощённое файловое хранилище `test`.

#### Scenario: E2E не подменяет сессии на mock_file
- **WHEN** выполняется проверка записи сессии в контуре E2E
- **THEN** сохранённая cookie-сессия присутствует в Redis с префиксом ключей
  шаблона (`sf_s:`)
- **AND** покрытие — PHPUnit в `tests/E2E/`

#### Scenario: Обычный test-контур остаётся без обязательного Redis для сессий
- **WHEN** выполняется suite Unit или Behat suite `default`
- **THEN** сессии приложения в `APP_ENV=test` используют упрощённое файловое
  хранилище (`mock_file`), и эти прогоны не требуют живого Redis ради сессий

### Requirement: Makefile и CI для E2E-контура
Шаблон MUST предоставлять Makefile-цели и CI-job’ы для E2E рядом с Unit и
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
Шаблон MUST включать дымовой E2E-сценарий открытия главной страницы (`/`) во
внешнем браузере — без продуктовых потоков Admin/login.

#### Scenario: Главная открывается в браузере
- **WHEN** E2E-клиент открывает `/`
- **THEN** текущий URL заканчивается на `/`
- **AND** покрытие — PHPUnit (`tests/E2E/`) и Behat (сценарий домена
  `admin_access` с тегом `@javascript`)
