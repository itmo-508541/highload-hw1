# Symfony-приложение

Техническая детализация **как** устроены локальная разработка и сопутствующие команды в этом репозитории. Обзор **что** установлено и зачем нужен этап — в [`docs/00-symfony.md`](../00-symfony.md).

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

- Основной рабочий путь: правки кода → локальная проверка → коммит.
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

Важно:

- production secrets задаются через переменные CI/CD и/или на сервере, а не в репозитории;
- значения в `.env` считаются шаблонными и должны быть заменены в реальной инфраструктуре в файле `.env.local`.
