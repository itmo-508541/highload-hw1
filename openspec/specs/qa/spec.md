## Purpose

Соглашение о слоях проверки качества в шаблоне: где живут unit/приёмочные
сценарии и что они не смешиваются с OpenSpec specs.

## Requirements

### Requirement: Раздельные каталоги для слоёв тестов и specs
Соглашение о поведении (OpenSpec), unit/integration (PHPUnit), приёмка (Behat)
и E2E (браузерный контур) MUST храниться в разных каталогах/suite и не сливаться
в один с OpenSpec specs.

#### Scenario: Расположение артефактов по слоям
- **WHEN** команда фиксирует ожидаемое поведение, unit-тест, приёмочный сценарий
  или E2E-сценарий
- **THEN** поведение описывается в `openspec/specs/**` (или delta в change)
- **AND** PHPUnit Unit/integration остаются в `tests/` вне `tests/E2E/`
- **AND** PHPUnit E2E остаются в `tests/E2E/`
- **AND** исполняемые Behat-сценарии «быстрой» приёмки остаются в `features/`
  (не в `features/e2e/`)
- **AND** Behat E2E остаются в `features/e2e/`
- **AND** каталоги `openspec/specs` и `features` не объединяются

### Requirement: PHPUnit и Behat как обязательные QA-инструменты шаблона
Шаблон MUST предоставлять конфигурацию и Makefile-цели для PHPUnit и Behat;
оба прогона (включая разделение Unit vs E2E и default vs e2e) выполняются в
Docker-сервисе `php`.

#### Scenario: Запуск слоёв через Makefile
- **WHEN** нужно прогнать unit/integration или приёмочные сценарии
- **THEN** используются `make test` (PHPUnit suite Unit, `phpunit.dist.xml`) и
  `make behat` (Behat suite `default`, `behat.yml.dist`, с подготовкой test-env
  через Phing)
- **AND** E2E-контур запускается отдельными целями `make test-e2e` /
  `make behat-e2e` (см. requirement про Makefile и CI для E2E)

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
- **THEN** используются `make test-e2e` (PHPUnit suite E2E) и
  `make behat-e2e` (Behat suite e2e, с подготовкой test-env через Phing как у
  `make behat`)
- **AND** покрытие harness — PHPUnit `tests/E2E/` и Behat `features/e2e/`

#### Scenario: CI разделяет Unit/Behat и E2E
- **WHEN** выполняется pipeline QA
- **THEN** job PHPUnit Unit не гоняет каталог `tests/E2E`
- **AND** существуют отдельные job’ы для PHPUnit E2E и Behat e2e с доступным
  Redis
- **AND** эти job’ы входят в mainline-гейт вместе с остальным QA

### Requirement: Минимальный E2E-harness главной страницы
Шаблон MUST включать дымовой E2E-сценарий открытия главной страницы (`/`) во
внешнем браузере — без продуктовых потоков Admin/login.

#### Scenario: Главная открывается в браузере
- **WHEN** E2E-клиент открывает `/`
- **THEN** текущий URL заканчивается на `/`
- **AND** покрытие — PHPUnit (`tests/E2E/`) и Behat (`features/e2e/`)
