## Purpose

Инварианты платформы проекта: где выполняется PHP/QA и как устроен локальный цикл
разработки (Docker, Makefile), без операционных runbook'ов.

## Requirements

### Requirement: PHP и QA только в Docker-сервисе php
Команды, затрагивающие PHP-код и PHP-окружение (Composer, Symfony Console,
PHPUnit, Behat, Phing, PHP CS Fixer, PHPStan, Rector, E2E-прогоны на базе
PHPUnit/Behat и бинарники из `vendor/bin/`), MUST выполняться только внутри
Docker-сервиса `php`, а не на хосте из корня репозитория.

#### Scenario: Запуск PHPUnit через фасад Makefile
- **WHEN** разработчик или CI запускает unit/integration-тесты из корня репозитория
- **THEN** прогон выполняется в контейнере сервиса `php` (типично через `make test`)
- **AND** результат не зависит от версии PHP/расширений, установленных на хосте

#### Scenario: Запуск E2E через фасад Makefile
- **WHEN** разработчик или CI запускает E2E-прогоны из корня репозитория
- **THEN** прогон выполняется в контейнере сервиса `php` (типично через
  `make test-env` / `make behat`)
- **AND** результат не зависит от браузера или PHP, установленных на хосте

#### Scenario: Запрет запуска PHP-инструментов на хосте
- **WHEN** агент или разработчик собирается выполнить `php`, `composer` или
  `vendor/bin/*` на хосте
- **THEN** вместо этого используется цель Makefile или `docker compose exec php …`

### Requirement: Makefile как фасад повседневных команд
Типовые операции локального цикла (поднять контейнеры, инициализация, зависимости,
QA включая E2E) MUST быть доступны через цели Makefile в корне репозитория.

#### Scenario: Первый запуск и ежедневный QA
- **WHEN** нужен первый подъём окружения или полный пакет проверок перед merge
- **THEN** используются `make up` / `make init` и `make qa` (или поштучные
  `make test` / `make test-env` / `make behat` /
  `make cs` / `make phpstan` / `make rector`)

### Requirement: Demo-режим читает env только из дампа
В demo-режиме (`docker-compose.yml` без override) приложение в сервисе `php` MUST
получать переменные окружения только из дампа `.env.local.php`, как на
production. Дамп SHALL собираться заново при каждом старте demo из цепочки
`.env` → `.env.local` → `.env.prod` → `.env.prod.local` (`.env.local` и
`.env.prod.local` — если существуют), до запуска `php`. Без `.env` сборка дампа
MUST завершаться ошибкой. Сервис `php` MUST NOT видеть `.env`-файлы.

#### Scenario: Первый старт demo
- **WHEN** разработчик запускает demo-режим на чистом окружении
- **THEN** до запуска `php` собирается дамп из `.env`, `.env.local` (если он
  есть), `.env.prod` и `.env.prod.local` (если он есть)
- **AND** приложение в `php` видит значения из этого дампа

#### Scenario: Значение из .env.local попадает в дамп
- **WHEN** разработчик задаёт значение в `.env.local`, которое `.env.prod` и
  `.env.prod.local` не переопределяют, и перезапускает demo
- **THEN** приложение видит значение из `.env.local`

#### Scenario: Без .env init падает
- **WHEN** в каталоге проекта нет `.env`
- **THEN** `init` завершается ошибкой
- **AND** `php` не стартует

#### Scenario: Правка env применяется перезапуском
- **WHEN** разработчик меняет значение в `.env.prod.local` и перезапускает demo
- **THEN** приложение видит новое значение
- **AND** удалять старый дамп вручную не требуется

#### Scenario: В php нет .env-файлов
- **WHEN** demo запущен
- **THEN** в контейнере `php` нет `.env`-файлов
- **AND** правка `.env*` на хосте без перезапуска demo не меняет env приложения

### Requirement: Запуск php в demo зависит от успешного init
В demo-режиме сервис `init` SHALL выполняться первым: собирать дамп env и
готовить БД и assets. Сервис `php` MUST стартовать только после успешного
завершения `init`.

#### Scenario: init завершился успешно
- **WHEN** `init` собрал дамп и подготовил БД без ошибок
- **THEN** `php` стартует, а за ним `nginx`

#### Scenario: init упал
- **WHEN** `init` завершается с ошибкой (дамп не собран или БД не подготовлена)
- **THEN** `php` не стартует
- **AND** запуск demo завершается ошибкой, а не работающим приложением без env

### Requirement: Dev-режим не использует дамп env
В dev-режиме (`docker-compose.yml` + `docker-compose.override.yml`) запуск MUST NOT
зависеть от `init`, и дамп `.env.local.php` MUST NOT создаваться: приложение,
тесты и Phing читают цепочку `.env`-файлов, а каждое окружение (`dev`, `test`,
`e2e`) получает свой `APP_ENV`. Сервис `init` в dev MUST быть выключен:
инициализация выполняется вручную через `make`.

#### Scenario: Старт dev без init
- **WHEN** разработчик выполняет `make up` в dev-режиме
- **THEN** `php` стартует, не дожидаясь `init`
- **AND** контейнер `init` не создаётся
- **AND** в корне проекта не появляется `.env.local.php`

#### Scenario: Тесты в dev идут в своём окружении
- **WHEN** разработчик в dev-режиме запускает `make test-env` или `make behat`
- **THEN** прогон идёт в окружении `test` (браузерный срез — в `e2e`), а не в `dev`
