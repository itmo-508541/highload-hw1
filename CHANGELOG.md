# Changelog

Все заметные изменения в этом проекте будут задокументированы в этом файле.

Формат файла основан на [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/),
и этот проект придерживается [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

<!-- Изменения в шаблоне Symfony backend: -->

### Added

- Подготовка окружения для Behat с доступом к MySQL: цель `make behat` перед `vendor/bin/behat` вызывает Phing с `-Dsymfony.env=test` и `-Denv.local.file=.env.test` (согласовано с job `qa:behat` в GitLab CI).
- `build.xml`: свойства `env.file` и `env.local.file`, чтобы подставлять нужные файлы окружения при загрузке переменных для Phing/Docker Compose.
- `.env.test`: `ENV_database_name=db_name_test` для имени тестовой БД в связке с Compose/CI.
- GitLab CI (`qa:behat`): сервис `mysql:8.0-debian` (alias `mysql`, `MYSQL_ROOT_PASSWORD`), подготовка через `phing` вместо копирования `.env.test` в `.env.local`.
- `src/Controller/IndexController.php`: маршрут `/` с пустым ответом — базовый HTTP-эндпоинт для сценариев.

### Changed

- `config/packages/test/doctrine.yaml`: `dbname_suffix` без литерала `_test` — только `%env(default::TEST_TOKEN)%` (имя тестовой БД задаётся через окружение).
- `tests/bootstrap.php`: явно `APP_ENV=test`, загрузка `.env` через `Dotenv::loadEnv(..., 'test', 'test')`, без подключения `config/bootstrap.php`.

**Затронутые пути:** `.env.test`, `.gitlab-ci.yml`, `Makefile`, `bin/console`, `build.xml`, `config/packages/test/doctrine.yaml`, `public/index.php`, `src/Controller/IndexController.php`, `tests/bootstrap.php`.

## [0.0.1-aplha]

### Added

- `PHPSTORM.md`: краткая интеграция PhpStorm и агента Cursor (ACP), ссылки на [документацию Cursor](https://cursor.com/docs/integrations/jetbrains), CLI и миграцию с JetBrains.
- `ABOUT.md`: двухуровневое описание — префикс **«Шаблон:»** для рамок репозитория-шаблона и **секции без префикса** с заглушками под продукт; HTML-комментарий для сопровождающих шаблон.
- `Makefile`: цель `make owner` — выравнивание владельца файлов на хосте под текущего пользователя после работы в контейнере от root (см. комментарий в `Makefile`).
- Cursor skill `.cursor/skills/phpunit-tests/SKILL.md`: работа с PHPUnit в Docker (`make test`), согласование с `phpunit.dist.xml` и `tests/`.
- Cursor skill `.cursor/skills/behat-tests/SKILL.md`: сценарии Behat в `features/`, контексты в `tests/Behat`, запуск в Docker (`make behat`), конфигурация `behat.yml.dist`.

### Changed

- `.cursor/agents/planner.md`: перед составлением плана обязательно читать `ABOUT.md` для контекста продукта, аудитории, ограничений и стека.
- `ABOUT.md`: продуктовые разделы **без** префикса «Проект:»; таблица «ключевые компоненты и технологии» только в продуктовом разделе (предзаполнение, правки на месте); блок **«Шаблон: ключевые компоненты…»** ранее удалён; вступление и шаг **после форка** (свой `#`, удалить от `## Шаблон:` до конца HTML-комментария); комментарий для сопровождения шаблона — сразу после «Шаблон: ограничения и допущения», с отсылкой к таблице компонентов и README / `general-rules.mdc`.
- `.cursor/agents/planner.md`, `.cursor/agents/worker.md`, `.cursor/agents/reviewer.md`, `.cursor/skills/subagent-workflow/SKILL.md`: зафиксирован Human in the loop — согласование scope, подтверждение черновика перед записью плана, подтверждение пути к плану и явные стопы на рискованных шагах у worker, разделение обязательных и спорных замечаний у reviewer (при отсутствии замечаний без лишних вопросов пользователю), ворота в оркестрации в skill workflow. `documenter.md` без изменений.
- `README.md`: раздел «Принципы работы backend-разработчика» — один принцип про команды только в контейнере `php` и приоритет `make`; владелец файлов на хосте сформулирован шире, пример `vendor/` и `config/` сохранён; для исправления прав — `make owner` вместо примера с `sudo chown`.
- `.cursor/rules/general-rules.mdc`: вместо заглушки зафиксирован технологический стек по `README.md` (Symfony 7.4, PHP 8.4, MySQL, Docker, Composer, Phing, QA-инструменты, GitLab CI/CD).
- `.cursor/rules/general-rules.mdc`: зафиксировано выполнение команд PHP/Symfony/Composer/QA только в контейнере `php`; приоритет целей `make` и README; прямой `docker compose exec php`, если нет подходящей цели в Makefile/README.
- `.cursor/agents/worker.md`: добавлены ссылки на скилл `behat-tests` и `make behat` рядом с PHPUnit.
- Скилл `brainstorming`: переведены `SKILL.md` и `dynamic-questioning.md` на русский; в шаблонах сохранены английские имена полей (**Question**, **Why This Matters**, **Purpose**, **Users**, **Scope**, P0/P1/P2 и т.д.) для совместимости с `planner.md`. Дополнительно русифицирован блок «3 вопроса до реализации» (шаги СТОП / Спросить / Подождать), метки Purpose/Users/Scope сохранены.
