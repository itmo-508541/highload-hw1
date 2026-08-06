# Symfony backend

Проект backend на Symfony 7.4 / PHP 8.4 с локальной разработкой в Docker и деплоем через GitLab CI/CD.

## Технологический стек

- PHP `8.4`
- Symfony `7.4.*`
- MySQL `8.0`
- Redis `8`
- Docker / Docker Compose
- Composer
- Phing (инициализация, миграции, deploy-команды)
- QA-инструменты: PHP CS Fixer, PHPStan, Rector, PHPUnit, Behat
- OpenSpec (процесс изменений: explore → propose → apply → archive); CLI через `npx`, Node.js ≥ 20.19

## Быстрый старт

```bash
cp docker-compose.override.yml.dist docker-compose.override.yml
make up
make init
```

Установка и обновление зависимостей (Composer в контейнере `php`):

```bash
make deps
```

Проверки перед commit:

```bash
make qa
```

OpenSpec (процесс изменений; локальный CLI из `package.json`, без глобальной установки):

```bash
npm ci
npx openspec --version
npx openspec validate --all --no-interactive
```

В Cursor: `/opsx-explore`, `/opsx-propose`, `/opsx-apply`, `/opsx-archive` и связанные skills.
Единый процесс ролей и артефактов: [openspec/process.md](openspec/process.md).

## Документация

| Вход | Зачем |
|------|--------|
| [ABOUT.md](ABOUT.md) | Продукт и рамки шаблона |
| [openspec/process.md](openspec/process.md) | Процесс изменений, роли, слои тестов, ADR, куда класть docs |
| [DEPLOYMENT.md](DEPLOYMENT.md) | CI-гейт, переменные, обзор деплоя → детали в `.gitlab-ci.yml` |
| [docs/redis.md](docs/redis.md) | Runbook: Redis / сессии |
| [docs/dotenv.md](docs/dotenv.md) | Runbook: Dotenv / env / prod-шим |
| [docs/phing.md](docs/phing.md) | Runbook: Phing / init / test-env |
| [docs/volume.md](docs/volume.md) | Runbook: именованный Docker volume |

## Начало работы

После шага из раздела «Быстрый старт» у вас уже есть локальный **`docker-compose.override.yml`** (его нет в репозитории: файл в **`.gitignore`**, создаётся копированием **`docker-compose.override.yml.dist`**).

В поле **`user`** задайте **`"<uid>:<gid>"`**, подставив вывод команд **`id -u`** и **`id -g`** на вашей машине. Тогда процесс сервиса **`php`** и файлы, которые приложение или **`docker compose exec php …`** создают в смонтированном каталоге **`./:/srv`**, на хосте будут принадлежать вашему пользователю.

Если **`user`** не задавать, образ по умолчанию работает от **`www-data`** (`Dockerfile`) — новые файлы на хосте окажутся с владельцем **`www-data`**. Если владелец дерева проекта всё же «съехал», из корня репозитория выполните **`make owner`** (внутри используется `sudo`; см. комментарий к цели в `Makefile`).

## Принципы работы backend-разработчика

- Пишите и проверяйте код в Docker-контуре проекта, чтобы окружение совпадало у команды и в CI.
- **Команды, затрагивающие PHP и инструменты вокруг него** (Composer, Symfony Console, PHPUnit, Behat, Phing, PHP CS Fixer, PHPStan, Rector и т.п.) **выполняйте только** в сервисе `php`. **Не** запускайте их на хосте — иначе версии PHP, расширений и пути разойдутся с CI и продом. Сначала смотрите цели в **`Makefile`**; если подходящей цели нет — `docker compose exec php …` из корня репозитория. Примеры: `make deps` или `docker compose exec php composer install`.
- Файлы репозитория внутри контейнера `php` смонтированы в `/srv/` (пути от корня проекта на хосте совпадают с ожидаемыми в командах ниже).
- Структура таблиц и индексы создаются автоматически, создавать новые миграции для этого не нужно; миграции Doctrine в проекте используются для добавления или изменения **данных**.
- Соблюдайте единый стиль кода (`.editorconfig`, `.php-cs-fixer.dist.php`) и не обходите проверки качества.
- Перед merge обязательно прогоняйте QA-набор (`php-cs-fixer`, `phpstan`, `rector`, `phpunit`, `behat`).
- Не храните production-секреты в репозитории и в коммитах. Для production используются CI-переменные и серверные env-файлы.
