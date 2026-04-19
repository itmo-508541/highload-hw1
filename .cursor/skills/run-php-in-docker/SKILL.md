---
name: run-php-in-docker
description: Запускает команды PHP и связанных инструментов только в Docker-сервисе `php` из корня репозитория: приоритет целей `make` и `README.md`, иначе `docker compose exec php`. Использовать при Symfony Console, Composer, PHPUnit, Behat, Phing, PHP CS Fixer, PHPStan, Rector, при упоминании контейнера `php`, `make up`, или когда агент собирается выполнить PHP/Composer на хосте.
---

# Запуск PHP-команд в Docker

## Правило

Все команды, затрагивающие **PHP-код** и **PHP-окружение** (и бинарники из `vendor/bin/`), выполняй **только** внутри сервиса **`php`**. Не запускай `php`, `composer` и скрипты из `vendor/bin/` **на хосте** из корня репозитория — версии PHP, расширений и пути не совпадут с CI/продом.

Рабочая директория: **корень репозитория** на хосте; в контейнере проект смонтирован в `/srv/` (пути к файлам те же).

## Порядок выбора команды

1. Контейнеры подняты: `make up` (если не запущены).
2. **Сначала** ищи цель в **`Makefile`** и инструкции в **`README.md`**.
3. Если готовой цели нет — **`docker compose exec php <команда>`** (из корня репозитория).

Для неинтерактивного ввода в скриптах/CI к `exec` иногда добавляют `-T` (см. цель `test` в `Makefile`).

## Эталонные цели `make`

| Задача | Команда |
|--------|---------|
| Поднять контейнеры | `make up` |
| Инициализация (Phing: storage, БД, миграции) | `make init` |
| Зависимости Composer | `make deps` |
| PHPUnit | `make test` — узкий прогон: `make test path/to/Test.php` или `make test ARGS="..."` |
| Behat | `make behat` — узкий прогон: `make behat features/foo.feature` или `make behat ARGS="..."` |
| PHP CS Fixer | `make cs` |
| PHPStan | `make phpstan` |
| Rector | `make rector` |
| Полный QA | `make qa` |
| Владелец файлов на хосте после работы от root в контейнере | `make owner` |

## Прямые вызовы (если `make` не подходит)

Все из **корня репозитория**, контейнер **`php`** запущен:

```bash
docker compose exec php composer <args>
docker compose exec php bin/console <args>
docker compose exec php phing <args>
docker compose exec php vendor/bin/phpunit <args>
docker compose exec php vendor/bin/behat <args>
docker compose exec php vendor/bin/php-cs-fixer <args>
docker compose exec php vendor/bin/phpstan <args>
docker compose exec php vendor/bin/rector <args>
```

## Заметки

- **Behat:** цель `make behat` перед `behat` вызывает Phing с тестовым окружением — для полного соответствия CI предпочитай её, а не голый `vendor/bin/behat`.
- **Права на хосте:** команды в контейнере часто от `root`; новые файлы в смонтированных каталогах могут стать `root` на хосте — см. `make owner` и `README.md`.

Подробности и примеры — в **`README.md`** (разделы про PHPUnit, Behat, быстрые команды).
