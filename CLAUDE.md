# Инструменты автоматизации в Grafana

PHP 8.4 · Symfony 7.4 · MySQL 8.0 · Redis 8 · Docker Compose

Приложение домашней работы HW1 (`HW1.md`): микросервис с REST-ручками над
записями (`/items`) и ручкой готовности (`/health`); на нём строятся метрики
золотых сигналов, генератор дашборда Grafana и нагрузочный скрипт.
Продукт — `ABOUT.md`. В репозитории живут **механизмы** — правила, скиллы,
каталоги форм, — а не накопленный опыт конкретного домена.
Бандлы: FOSRest, LexikJWT, NelmioApiDoc, SonataAdmin.

## Constraints

- `composer`, `phing`, `php`, `bin/console`, `vendor/bin/*` — **только в
  Docker-сервисе `php`**: `docker compose exec php <cmd>`. На хосте запускать
  нельзя: версии PHP-расширений хоста и образа разойдутся.
  Shell: `required_permissions: ["all"]`. Предпочитать цели `Makefile`.
- Контейнеры не подняты → `make up`.
- `vendor/` gitignored → поиск по умолчанию его **не видит** и отдаёт пустой
  результат без ошибки. Пустой результат ≠ «кода нет». Искать в зависимостях,
  передавая путь явно: `rg -n "<шаблон>" vendor/`. Определение класса по FQCN —
  скилл `vendor-class-lookup`, не веб.
- Версии здесь свежее знания модели (Symfony 7.4, Doctrine ORM 3.6, PHPUnit 13,
  Sonata Admin 4.43). API по памяти не восстанавливать.
- Секреты production — не в репозитории.
- Вложенный `CLAUDE.md` — инструкция исполнителю в этом каталоге, а не
  документация: только то, без чего здесь нельзя выполнить задачу; обоснования —
  в `docs/adr/`.

## Каталоги

| Каталог | Назначение |
|---------|-----------|
| `src/` | Код приложения (Symfony) |
| `tests/` | PHPUnit-тесты |
| `features/` | Behat: сценарии Gherkin, контексты в `features/bootstrap/` |
| `patterns/tests/` | Каталог механик тестов |
| `migrations/` | Doctrine-миграции (только данные, не DDL) |
| `docs/runbooks/` | Операционные runbook'и |
| `docs/adr/` | Architecture Decision Records |
| `openspec/` | Спецификации поведения и changes |
| `config/` | Конфигурация Symfony |
| `docker/` | Конфиги образов: `php`, `php-dev`, `nginx`, `nginx-dev`, `git` |
| `tools/` | Обвязка phing: bootstrap и задача `dotEnvProperty` |
| `secrets/` | Шаблоны секретов для деплоя (значений нет) |
| `build/` | Временные файлы, gitignored. Не источник правды |
| `vendor/` | Зависимости Composer (gitignored, см. Constraints) |

## Environments

- **dev** — `docker compose`: `docker-compose.yml` + `docker-compose.override.yml`
  (gitignored, копия `.dist`): `target: dev`, проект смонтирован в `/srv`.
  Всё, что нужно только разработке, — в `.dist`. Порты: `80` (nginx),
  `8080` (phpmyadmin), `3306`.
- **demo** (prod-режим) — `docker-compose.yml` без override: образ `target: prod`,
  `APP_ENV=prod`, одноразовый сервис `init` выполняет `phing`. Только для показа.
- **test** — `APP_ENV=test`, задан в `phpunit.dist.xml` и в ядре Behat.
- **e2e** — приложение под браузером Panther: `PANTHER_APP_ENV=e2e`, `.env.e2e`.
- **production** — `docker-compose.deploy.yml`, конфиг собирается в CI.

Цепочка `.env` и prod-шим — `docs/runbooks/dotenv.md`.

## QA-гейт

`make qa` — запускает **пользователь** перед push.
Собирает `cs`, `phpstan`, `rector`, `test`, `test-env`, `behat`.

## Agent skills

`.claude/skills/`. Правила их написания — `.claude/skills/CLAUDE.md`.

| Скилл | Когда |
|---|---|
| `testing-strategy` | выбрать инструмент, место и тип теста |
| `phpunit-tests` | писать и гонять PHPUnit-тест, когда suite уже выбран |
| `behat-tests` | писать и гонять Behat-сценарий, когда домен уже выбран |
| `brownfield-map` | описать существующее поведение legacy-кода по доказательствам |
| `tests-pattern-catalog` | пополнить `patterns/tests/` по указанию человека |
| `skill-retro` | оценить скилл по произведённому им артефакту |
| `vendor-class-lookup` | прочитать определение класса по FQCN |
| `add-docker-volume` | завести именованный том: compose, CI, права на сервере |
