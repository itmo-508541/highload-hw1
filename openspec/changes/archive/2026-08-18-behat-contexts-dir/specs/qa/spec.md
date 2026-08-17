## MODIFIED Requirements

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
- **AND** PHPUnit Unit/integration остаются в `tests/` вне `tests/E2E/`
- **AND** PHPUnit E2E остаются в `tests/E2E/`
- **AND** исполняемые Behat-сценарии «быстрой» приёмки остаются в `features/`
  (не в `features/e2e/`)
- **AND** Behat E2E остаются в `features/e2e/`
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
