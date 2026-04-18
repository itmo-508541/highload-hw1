# Symfony backend

Проект backend на Symfony 7.4 / PHP 8.4 с локальной разработкой в Docker и деплоем через GitLab CI/CD.

## Технологический стек

- PHP `8.4`
- Symfony `7.4.*`
- MySQL `8.0`
- Docker / Docker Compose
- Composer
- Phing (инициализация, подготовка storage, миграции, deploy-команды)
- QA-инструменты: PHP CS Fixer, PHPStan, Rector, PHPUnit, Behat

## Принципы работы backend-разработчика

- Пишите и проверяйте код в Docker-контуре проекта, чтобы окружение совпадало у команды и в CI.
- **Команды, затрагивающие PHP и инструменты вокруг него** (Composer, Symfony Console, PHPUnit, Behat, Phing, PHP CS Fixer, PHPStan, Rector и т.п.) **выполняйте только** в сервисе `php` (контейнеры должны быть запущены, см. раздел «Локальный цикл разработки» и `make up`). **Не** запускайте их на хосте из корня проекта — иначе версии PHP, расширений и пути разойдутся с CI и продом. Сначала смотрите цели **`make`** и разделы ниже в этом README; если подходящей цели нет — `docker compose exec php …` из корня репозитория. Примеры: `make deps` или `docker compose exec php composer install`.
- **Владелец файлов на хосте:** команды в контейнере часто выполняются от `root`, поэтому новые или изменённые файлы в смонтированных каталогах могут оказаться с владельцем `root` на хосте — не только после Composer. Типичный случай: каталоги `vendor/` и `config/` после установки зависимостей и рецептов **Symfony Flex**. Тогда IDE и ваш пользователь могут не иметь прав на запись. Из **корня репозитория на хосте** выполните **`make owner`** — цель в `Makefile` выравнивает владельца дерева под текущего пользователя (внутри вызывается `sudo`; см. комментарий к цели в `Makefile`).
- Файлы репозитория внутри контейнера `php` смонтированы в `/srv/` (пути от корня проекта на хосте совпадают с ожидаемыми в командах ниже).
- Структура таблиц и индексы создаются автоматически, создавать новые миграции для этого не нужно
  - миграции Doctrine в нашем проекте используются для добавления или изменения данных
- Соблюдайте единый стиль кода (`.editorconfig`, `.php-cs-fixer.dist.php`) и не обходите проверки качества.
- Перед merge обязательно прогоняйте QA-набор (`php-cs-fixer`, `phpstan`, `rector`, `phpunit`, `behat`).
- Любые изменения схемы БД сопровождайте корректным циклом миграций и проверкой на локальном окружении.
- Не храните production-секреты в репозитории и в коммитах. Для production используются CI-переменные и серверные env-файлы.

## Локальный цикл разработки

### 1) Первый запуск проекта

```bash
make up
make init
```

Что делает:
- `make up` поднимает контейнеры.
- `make init` запускает `phing` внутри `php`-контейнера, что приводит к:
  - подготовке файлового хранилища (`storage-prepare`), если потребуется;
  - подготовке БД и миграциям (`database-deploy`).

### 2) Установка/обновление зависимостей

Предпочтительно через Makefile (Composer выполняется в контейнере `php`, см. раздел выше):

```bash
make deps
```

Эквивалент без Makefile:

```bash
docker compose exec php composer install
```

### 3) Ежедневная разработка

- Основной рабочий путь: правки кода -> локальная проверка -> коммит.
- Для ручных запусков используйте `docker compose exec php ...`.

### 4) Обязательные проверки перед merge

Полный пакет:

```bash
make qa
```

Поштучно:

```bash
make cs
make phpstan
make rector
make test
make behat
```

### PHPUnit

Конфигурация: `phpunit.dist.xml`, bootstrap: `tests/bootstrap.php`. Запуск из корня репозитория (контейнер `php` должен быть запущен, см. `make up`):

```bash
# весь набор тестов
make test

# один файл или каталог (пути относительно корня проекта)
make test tests/EventListener/CorsRequestListenerTest.php

# то же через переменную ARGS (удобно, если не хотите передавать путь второй «целью» make)
make test ARGS="tests/EventListener/CorsRequestListenerTest.php"
```

Прямой вызов без Makefile:

```bash
docker compose exec php vendor/bin/phpunit
docker compose exec php vendor/bin/phpunit tests/EventListener/CorsRequestListenerTest.php
```

### Behat

Конфигурация: `behat.yml.dist`, bootstrap: `tests/bootstrap.php`. При необходимости локальных правок скопируйте `behat.yml.dist` в `behat.yml` (файл не коммитится). Цель `make behat` перед `vendor/bin/behat` запускает Phing с `-Dsymfony.env=test` и `-Denv.local.file=.env.test`, чтобы подготовить тестовое окружение и доступ к БД (как в CI). Запуск из корня репозитория (контейнер `php` должен быть запущен, см. `make up`):

```bash
# весь набор сценариев
make behat

# один feature-файл (пути относительно корня проекта)
make behat features/demo.feature

# то же через переменную ARGS (удобно, если не хотите передавать путь второй «целью» make)
make behat ARGS="features/demo.feature"
```

Прямой вызов без Makefile:

```bash
docker compose exec php vendor/bin/behat
docker compose exec php vendor/bin/behat features/demo.feature
```

## Быстрые команды

```bash
make up
make init
make deps
make qa
```

## Переменные окружения и конфигурация

Порядок загрузки env в Symfony:
- `.env` (базовые значения для проекта);
- `.env.local` (локальные переопределения, не коммитятся);
- `.env.$APP_ENV` и `.env.$APP_ENV.local` (по окружениям).

Ключевые backend-группы переменных:
- БД: `ENV_database_host`, `ENV_database_port`, `ENV_database_name`, `ENV_database_user`, `ENV_database_password`, `ENV_database_root_password`.
- Приложение: `ENV_secret`, `ENV_debug`.
- Почта: `ENV_mailgun_host`, `ENV_mailgun_username`, `ENV_mailgun_password`.

Важно:
- production secrets задаются через переменные CI/CD и/или на сервере, а не в репозитории;
- значения в `.env` считаются шаблонными и должны быть заменены в реальной инфраструктуре в файле `.env.local`.
