## Purpose

Соглашение о слоях проверки качества в шаблоне: где живут unit/приёмочные
сценарии и что они не смешиваются с OpenSpec specs.

## Requirements

### Requirement: Раздельные каталоги для слоёв тестов и specs
Соглашение о поведении (OpenSpec), unit/integration (PHPUnit) и приёмка (Behat)
MUST храниться в разных каталогах и не сливаться в один.

#### Scenario: Расположение артефактов по слоям
- **WHEN** команда фиксирует ожидаемое поведение, unit-тест или приёмочный сценарий
- **THEN** поведение описывается в `openspec/specs/**` (или delta в change)
- **AND** PHPUnit-тесты остаются в `tests/`
- **AND** исполняемые Behat-сценарии остаются в `features/`
- **AND** каталоги `openspec/specs` и `features` не объединяются

### Requirement: PHPUnit и Behat как обязательные QA-инструменты шаблона
Шаблон MUST предоставлять конфигурацию и Makefile-цели для PHPUnit и Behat;
оба прогона выполняются в Docker-сервисе `php`.

#### Scenario: Запуск слоёв через Makefile
- **WHEN** нужно прогнать unit/integration или приёмочные сценарии
- **THEN** используются `make test` (PHPUnit, `phpunit.dist.xml`) и
  `make behat` (Behat, `behat.yml.dist`, с подготовкой test-env через Phing)
