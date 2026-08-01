## Purpose

Инварианты платформы шаблона: где выполняется PHP/QA и как устроен локальный цикл
разработки (Docker, Makefile), без операционных runbook'ов.

## Requirements

### Requirement: PHP и QA только в Docker-сервисе php
Команды, затрагивающие PHP-код и PHP-окружение (Composer, Symfony Console,
PHPUnit, Behat, Phing, PHP CS Fixer, PHPStan, Rector и бинарники из `vendor/bin/`),
MUST выполняться только внутри Docker-сервиса `php`, а не на хосте из корня
репозитория.

#### Scenario: Запуск PHPUnit через фасад Makefile
- **WHEN** разработчик или CI запускает unit/integration-тесты из корня репозитория
- **THEN** прогон выполняется в контейнере сервиса `php` (типично через `make test`)
- **AND** результат не зависит от версии PHP/расширений, установленных на хосте

#### Scenario: Запрет запуска PHP-инструментов на хосте
- **WHEN** агент или разработчик собирается выполнить `php`, `composer` или
  `vendor/bin/*` на хосте
- **THEN** вместо этого используется цель Makefile или `docker compose exec php …`

### Requirement: Makefile как фасад повседневных команд
Типовые операции локального цикла (поднять контейнеры, инициализация, зависимости,
QA) MUST быть доступны через цели Makefile в корне репозитория.

#### Scenario: Первый запуск и ежедневный QA
- **WHEN** нужен первый подъём окружения или полный пакет проверок перед merge
- **THEN** используются `make up` / `make init` и `make qa` (или поштучные
  `make test` / `make behat` / `make cs` / `make phpstan` / `make rector`)
