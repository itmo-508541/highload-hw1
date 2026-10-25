# Design

## Context

Мотивация — `proposal.md`, раздел Why. Требования — `specs/platform/spec.md`.
Текущее поведение с указателями — раздел «Brownfield-поведение» ниже. Подход
определяют три его факта:

- `dotenv_overload: true` (`composer.json:96`): если `.env.local.php` существует,
  он применяется всегда, вместе со своим `APP_ENV` (риск R2).
- В demo php и init получают `.env`-файлы bind-mount'ом с хоста
  (`docker-compose.yml:53-54`), а init стартует параллельно с php (R1, R4).
- В prod-образе нет composer (`Dockerfile:47`, только стадия `dev`), а сервисы
  работают с `read_only: true` (`docker-compose.yml:2`): `/srv` недоступен для записи.

## Goals / Non-Goals

**Goals:**
- Demo-php загружает env тем же путём, что production: шим `/srv/.env.local.php`
  → дамп из места, которое готовит не php.
- Порядок demo: mysql/redis → init → php → nginx. Упавший init останавливает запуск.

**Non-Goals:**
- Dev-режим, test/e2e, CI-пайплайн, `docker-compose.deploy.yml`, `secrets/env.php`.
- Смена `dotenv_overload` и исправление R2 в целом: change лишь не создаёт условие,
  при котором R2 срабатывает.
- `target: dev` у nginx в demo (`docker-compose.yml:13`) — замечено, вне change.

## Decisions

**D1. Дамп создаётся только в demo; в dev его нет.** Из-за R2 дамп в dev
перекрыл бы `APP_ENV` у phpunit, phing `-Dapp.env=test` и Panther (`e2e`).
`bin/console --env=…` упал бы с `LogicException`.
Отвергнуты: отключить `dotenv_overload` (меняет приоритет переменных контейнера
над `.env` во всех режимах, отдельное решение); dev-шим, подключающий дамп только
при `APP_ENV=dev` (самописный механизм вместо штатного).
Решение фиксируется в **ADR `docs/adr/0005`**: почему загрузка env в локальных
режимах различается и почему не отключён overload. Остальные обоснования
документации — там же (D8).

**D2. Дамп пересобирается при каждом старте и хранится в именованном томе
`dotenv`** (`/run/dotenv`: init — rw, php — ro). Требование «удалил — сформировался
заново» выполняется само собой, устаревшего дампа нет.
Отвергнуто: создавать только при отсутствии и хранить на хосте (`build/`).
Правка `.env*` молча не применяется, а bind-каталог, который Docker создаёт
сам, принадлежит root, и init под `www-data` писать в него не может.

**D3. Дамп собирает скрипт `tools/env-dump.php <каталог-dotenv> <файл-дампа>`.**
Он разбирает явный список `.env` → `.env.local` (если есть) → `.env.$APP_ENV` →
`.env.$APP_ENV.local` (если есть) — порядок `loadEnv` и `symfony:dump-env` —
парсером Symfony Dotenv и пишет `var_export`-массив с `APP_ENV` из окружения
процесса. `.env` обязателен. Ненулевой код возврата означает ошибку, например
нет `.env` или синтаксическая ошибка.
- Не `bootEnv`: тот прочитал бы прежний дамп через шим (R2), и новый дамп
  собрался бы из старого.
- Не `loadEnv` целиком: при отсутствии `.env` он молча берёт `.env.dist`
  (`Dotenv.php:118`), а спека требует падать.
- Отвергнуты `composer symfony:dump-env` (composer нет в prod-образе) и
  `dotenv:dump` (не зарегистрирован, пишет только рядом с `.env`, а `/srv` read_only).
- Аргументы-пути делают скрипт проверяемым на фикстурах (Предусловие П1).
- `tools/` уже держит обвязку Dotenv (`phing-bootstrap.php`) и попадает
  в prod-образ: в `Dockerfile:87-100` его нет в списке исключений.
  Строку `tools/` в корневом `CLAUDE.md` дополнить.

**D4. Init получает каталог проекта read-only (`.:/run/dotenv-src:ro`), а не
отдельные `.env`-файлы.** Bind несуществующего `.env.prod.local` Docker превратил
бы в root-каталог на хосте. Скрипт сам выбирает файлы, поэтому в дамп попадают
только файлы списка D3. Монтирование только в init, только
локально, только на чтение.

**D5. Шим `secrets/env-demo.php`** (`return include '/run/dotenv/.env.local.php';`)
монтируется в `/srv/.env.local.php` в php и в init. Production-шим
`secrets/env.php` не трогается. Init выполняет
`sh -c "php tools/env-dump.php … && exec phing"`. Phing стартует уже после дампа,
его bootstrap читает свежий дамп через шим и видит ровно то, что увидит php.
Поэтому битый дамп роняет init, а не приложение.
Отвергнута phing-цель `env-dump` внутри `phing`: bootstrap Phing вызывает
`bootEnv` до любой цели (`tools/phing-bootstrap.php:33`) и прочитал бы прежний дамп.

**D6. Связи сервисов.**
- `php.depends_on += init: service_completed_successfully`.
- `init.depends_on` — `mysql: service_started`, вместо нынешней зависимости
  от php. Готовность БД ждёт `database-ready` (`build.xml:57-67`).
- php в demo теряет bind-mount'ы `.env`/`.env.prod` и получает
  `dotenv:/run/dotenv:ro` и шим.
- В `docker-compose.override.yml.dist` — `php.depends_on: !override` (redis
  healthy, mysql started). Проверено в scratchpad на Compose v5.6.1:
  - без этого dev-проект невалиден: `service "php" depends on undefined service "init"`;
  - `required: false` не годится: php стартует и после упавшего init.
- `init` в dev выключен намеренно: инициализация dev — только `make`
  (`make init` → `exec php phing`, `Makefile:5-6`). В override у `init`:
  - `profiles: ["disabled"]` — имя говорит, что сервис выключен, а не ждёт
    своего профиля (прежний профиль `init` читался как рабочий, R3);
  - `command: ["false"]` — ничего не выполняет и завершается кодом 1.
    Унаследованная из базы команда собрала бы дамп в томе `dotenv`, а D1 дамп
    в dev запрещает; случайный `--profile disabled` заметен ошибкой;
  - без `user: "1001:1001"`: заглушка ничего не пишет.

**D7. Каталог `/run/dotenv` создаётся в стадии `php` `Dockerfile` с владельцем
`www-data`.** Пустой именованный том наследует владельца из образа, иначе init
не сможет писать. Правка — одна строка `install -d` в `Dockerfile`, напрямую.
Скилл `add-docker-volume` не применяется: его рецепт — том с bind на каталог
хоста, одинаковый для dev и production (`ENV_*_storage`, `REQUIRED_VARS` в CI,
права на сервере), а этот том — обычный именованный, без bind и только в demo.

**D8. Обоснования — только в ADR 0005.** Runbook `dotenv.md` даёт порядок,
команды и ловушки, `README.md` — запуск и ссылку на runbook; каждое «почему» в
них — ссылка на ADR. ADR дополняется обоснованиями demo-механики: пересборка
дампа при каждом старте (D2), скрипт вместо `bootEnv`/`loadEnv`/`dump-env` (D3),
шим и `phing` после дампа (D5), выключенный `init` в dev (D6), перезапуск через
`down` + `up`. Причина: довод «дамп + overload → чужой `APP_ENV`» уже записан
трижды (ADR, runbook, docblock `TestEnvironmentTest`) и разъезжается, а после
архивации change долговечным местом останется только ADR. Отступление от
`docs/CLAUDE.md` («решения по отдельной задаче живут в артефактах change») —
по указанию пользователя.

## Risks / Trade-offs

- [Правка `.env*` в demo не видна без перезапуска] → так и задумано, это
  **BREAKING** из proposal. Описать в runbook `dotenv.md`.
- [Init видит весь репозиторий, включая `config/jwt`] → только локально и только
  на чтение. В дамп попадает лишь список из D3.
- [`docker compose up php --no-deps` поднимет php без init с дампом прошлого
  старта] → осознанный обход порядка. Том хранит последний успешный дамп.
- [`docker compose up -d` поверх запущенного demo: если `init` упадёт, уже
  работающий php не останавливается и отдаёт прежний дамп] → runbook даёт
  перезапуск через `docker compose down` и `docker compose up -d`.
- [Каждый старт demo прогоняет миграции и `schema:update`] → так init работает
  и сейчас (`build.xml:9`), change это не добавляет.
- [Дамп с секретами лежит в томе открытым текстом] → как на production
  (`/run/secrets/.env.local.php`). Только demo.
- [Ручной `.env.local.php` в dev по-прежнему запускает R2] → вне change. Его ловит
  строка S7 стратегии тестирования.

## Migration Plan

1. `docker compose -f docker-compose.yml build` — образ с `/run/dotenv`.
2. `docker compose -f docker-compose.yml up -d`: init создаёт том и дамп, затем
   стартует php.
3. Разработчикам dev: скопировать обновлённый `docker-compose.override.yml.dist`
   в `docker-compose.override.yml`, иначе Compose отвергнет проект (D6).

Откат: revert change, `docker volume rm <проект>_dotenv`.

## Brownfield-поведение

### 1. Context Summary

Поведение: как стартуют `init`/`php`/`nginx` в demo и dev и откуда приложение,
Phing и тесты берут env.

| Артефакт | Что дал |
|---|---|
| `docker-compose.yml` | сервисы, якорь `*php`, `init` с `depends_on: php`, bind-mount'ы `.env` |
| `docker-compose.override.yml.dist` | dev: `APP_ENV=dev`, `.:/srv`, `init` в профиле `init` |
| `Dockerfile` | `APP_ENV=prod` в prod, исключение `.env*` из образа, composer только в `dev` |
| `composer.json` | `extra.runtime.dotenv_overload: true` |
| `vendor/symfony/dotenv/Dotenv.php` | ветвление `bootEnv` по `.env.local.php` |
| `vendor/symfony/runtime/SymfonyRuntime.php` | запрет `--env` при overload |
| `tools/phing-bootstrap.php`, `tests/bootstrap.php` | явный `bootEnv(..., overrideExistingVars: true)` |
| `phpunit.dist.xml`, `behat.yml.dist` | `APP_ENV=test` / `e2e` для прогонов |
| `build.xml`, `Makefile` | что делает init (`phing` → `init`), как dev готовит БД |
| `.gitlab-ci.yml`, `docker-compose.deploy.yml`, `secrets/env.php` | production-путь env: дамп → secret → шим |
| `docker/php/fpm/pool.d/www.conf` | `clear_env = no` |
| `tests/Unit/Tools/DotEnvPropertyTaskTest.php` | существующая фиксация: дамп перекрывает env в Phing |
| Наблюдение: `docker compose -f docker-compose.yml config --format json` и `docker compose config --format json` | эффективные `depends_on`, тома, профили для demo и dev |
| Наблюдение: compose-файлы `alpine` в scratchpad, `docker compose up` | поведение `depends_on` на сервис в выключенном профиле и `required: false` при упавшей зависимости |

### 2. Behavior Map

**Точки входа**
- demo: `docker compose -f docker-compose.yml up` (наблюдение `config`).
- dev: `make up` (`Makefile:8`), `make init` → `exec php phing` (`Makefile:5-6`),
  `make test-prepare` → `phing -Dapp.env=test`.
- Приложение: `public/index.php:7`, `bin/console:15` — через Symfony Runtime.
- Тесты: `tests/bootstrap.php`. Panther-сервер — `APP_ENV=e2e`
  (`behat.yml.dist:39`, `tests/E2E/HomepageTest.php:19`).
- CI: `qa:tools` и `deploy-mainline:prepare-secrets` — `composer symfony:dump-env prod`
  (`.gitlab-ci.yml:290`, `.gitlab-ci.yml:335`).

**Аргументы и возвращаемые значения**
- `APP_ENV`:
  - prod-образ — `Dockerfile:85`;
  - dev — `docker-compose.override.yml.dist:11`;
  - phpunit — `phpunit.dist.xml:16`, `tests/bootstrap.php:9`;
  - phing — `-Dapp.env` (`tools/phing-bootstrap.php:13-16`).
- В `.env` ключа `APP_ENV` нет: `.env:1-57`.

**Коллабораторы**
- `Dotenv` создаётся внутри Runtime (`SymfonyRuntime.php:130`) и через `new`
  в bootstrap'ах (`tools/phing-bootstrap.php:33`, `tests/bootstrap.php:15`).

**Внешние границы**
- ФС, demo: `.env` и `.env.prod` — bind ro (`docker-compose.yml:53-54`),
  у init те же, через якорь (наблюдение `config`).
- ФС, dev: весь проект (`docker-compose.override.yml.dist:18-19`).
- Окружение процесса: переменные контейнера доходят до FPM (`www.conf:10`).
- БД: phing `database-deploy` (`build.xml:32-55`), ожидание через `nc` (`build.xml:57-67`).
- Том `static` — assets для nginx (`docker-compose.yml:27,52`).

**Побочные эффекты (demo, по порядку)** — наблюдение `config`:
1. Стартуют mysql и redis.
2. php — после `mysql: service_started` и `redis: service_healthy`
   (`docker-compose.yml:38-42`).
3. init — после `php: service_started` (`docker-compose.yml:59-60`). Он выполняет
   `phing` → `init`: БД, пользователь, `schema:update`, миграции, `assets:install`
   в том `static` (`build.xml:9,26-55`).
4. nginx — после `php: service_started` (`docker-compose.yml:20-22`). Шаги 3 и 4
   друг друга не ждут.

**Ветвление**
- `bootEnv`: если `.env.local.php` даёт массив и `overrideExistingVars` истинно,
  применяется дамп целиком. Иначе дамп применяется, только если его `APP_ENV`
  совпадает с текущим; в остальных случаях — `loadEnv` по цепочке `.env*`
  (`Dotenv.php:171-176`). При overload первая ветвь безусловна.
- Runtime при overload: `--env`, отличный от итогового `APP_ENV`, →
  `LogicException` (`SymfonyRuntime.php:144-146`).
- dev: `init` вне активного профиля и в эффективном конфиге отсутствует
  (наблюдение `config`).

**Терминальные значения**
- Код возврата init — код phing (`checkreturn="true"` у всех `exec`, `build.xml`).
- Падение init сейчас ни на что не влияет: от init никто не зависит
  (наблюдение `config`).

### 3. Швы наблюдения

| Граница | Вид шва | Что подменяется или читается | Чего шов не даёт |
|---|---|---|---|
| `.env`-файлы проекта | файловая система | каталог dotenv передаётся аргументом (`bootEnv($path)`, будущий `tools/env-dump.php`) | не показывает, что именно смонтировано в контейнер demo |
| дамп `.env.local.php` | наблюдение артефакта | произведённый файл читается `include` | не показывает, что php читает именно его (это шим и монтирование) |
| `APP_ENV` процесса | окружение процесса | `$_SERVER['APP_ENV']` до `bootEnv` (`tests/bootstrap.php:9`) | не отличает источник значения: контейнер или дамп |
| порядок сервисов и монтирования Compose | шва нет | искались: коллаборатор, наследование, контейнер, namespace-шов, окружение, ФС, артефакт. Порядок задаёт Compose вне PHP-кода, а в php-образе нет docker CLI (`Dockerfile:12-25`). Карточки в `patterns/tests/INDEX.md` нет | ни порядка, ни состава томов другого контейнера |
| вывод phing (наблюдение) | наблюдение артефакта | код возврата и stdout процесса (`DotEnvPropertyTaskTest`) | только то, что phing печатает |
| ядро в `test` (наблюдение) | окружение процесса | окружение ядра после `tests/bootstrap.php` | e2e-сервер Panther живёт в отдельном процессе, его окружение не видно |

### 4. Constraints / Unknowns

- Работающий demo в этой сессии не поднимался (контейнеры остановлены). Порядок
  старта установлен по `config` и по опыту в scratchpad, а не по логам demo.
- Предел инструмента: PHPUnit работает внутри контейнера php и не видит ни
  Compose, ни файловую систему других контейнеров.

### 5. Fragile / High-Risk Paths

| id | Что не так | Указатель | Наблюдаемое следствие |
|---|---|---|---|
| R1 | init в demo ждёт только старта php, php не ждёт init | `docker-compose.yml:59-60` | php и nginx отвечают до конца миграций и `assets:install`. Падение init не мешает demo «работать» |
| R2 | при `dotenv_overload` существующий `.env.local.php` перекрывает `APP_ENV` во всех процессах проекта | `composer.json:96`, `Dotenv.php:171`, `tools/phing-bootstrap.php:33`, `tests/bootstrap.php:15` | phpunit, phing `-Dapp.env=test` и Panther работают в окружении дампа. `bin/console --env=…` падает (`SymfonyRuntime.php:145`) |
| R3 | профиль `init` в dev нигде не используется | `docker-compose.override.yml.dist:21-23` | `make up` init не запускает, `make init` идёт через `exec php` |
| R4 | demo-php читает `.env`-файлы с хоста напрямую, а не дамп | `docker-compose.yml:53-54` | ошибка сборки дампа не видна до деплоя. Правка `.env` на хосте видна php без перезапуска |

### 6. Notes / Gaps

- **Допущение:** нынешний demo-init проходит `phing` при `read_only`. Не
  подтверждено прогоном в этой сессии.
- Уже покрыто:
  - `tests/Unit/Tools/DotEnvPropertyTaskTest.php` — `testEnvLocalPhpOverridesEnv`
    (дамп перекрывает env в Phing, R2 на уровне Phing), `testAppEnvTestReadsEnvChain`;
  - `tests/Unit/Command/GenerateSecretCommandBootEnvTest.php` — цепочка `.env` через `bootEnv`.
- Нет автоматизации вовсе: Compose-порядок, содержимое томов, demo целиком.
- Вне change: `docs/runbooks/phing.md` ссылается на несуществующий
  `features/phing_dotenv_property_task.feature`. Nginx в demo собирается
  из `target: dev` (`docker-compose.yml:13`).

### 7. Confidence

| Область | Уровень | Почему |
|---|---|---|
| ветвление `bootEnv` и запрет `--env` | High | прочитан код vendor, подтверждено существующим тестом `DotEnvPropertyTaskTest` |
| эффективный конфиг Compose demo/dev | High | наблюдение `docker compose config` |
| поведение `depends_on` с профилем и `required: false` | High | воспроизведено в scratchpad на той же версии Compose |
| фактический порядок и тайминг старта demo | Medium | выведен из `depends_on`, логи demo не наблюдались |
| что делает init при `read_only` | Low | допущение, прогона нет |

## Стратегия тестирования

**PHPUnit**

| Сценарий спеки | Точка наблюдения | Suite | Тип теста | Что нужно снаружи | Базовый класс | Состояние | Обоснование |
|---|---|---|---|---|---|---|---|
| Первый старт demo и «Значение из .env.local попадает в дамп» — сужено: дамп собирается из `.env`, `.env.local`, `.env.prod` в порядке Symfony | вывод внешнего процесса: файл дампа после `tools/env-dump.php` | Unit | новое поведение | ничего | `TestCase` | новая | проверка `EnvDumpScriptTest`, набор «без `.env.prod.local`, с `.env.local`»: `FIXTURE_LOCAL=from_local`, `FIXTURE_VALUE=from_prod` (`.env.prod` перекрывает `.env.local`) |
| Правка env применяется перезапуском — сужено: повторный запуск перезаписывает прежний дамп значением из `.env.prod.local` | вывод внешнего процесса: файл дампа после повторного запуска | Unit | новое поведение | ничего | `TestCase` | новая | проверка `EnvDumpScriptTest`, набор «старый дамп + `.env.prod.local` перекрывает `.env.prod`» |
| init упал — сужено: сборка дампа с ошибкой даёт ненулевой код возврата | вывод внешнего процесса: код возврата | Unit | новое поведение | ничего | `TestCase` | новая | отдельная проверка в `EnvDumpScriptTest`: иной способ наблюдения — код, а не файл |
| Без .env init падает — сужено: скрипт без `.env` даёт ненулевой код возврата | вывод внешнего процесса: код возврата | Unit | новое поведение | ничего | `TestCase` | новая | отдельная проверка в `EnvDumpScriptTest`, набор «нет `.env`» |
| Тесты в dev идут в своём окружении — сужено: ядро phpunit работает в `test` | сервис из контейнера: окружение загруженного ядра | Integration | characterization | ядро | `KernelTestCase` | новая | `tests/Integration/TestEnvironmentTest.php`: утверждение «окружение ядра — `test`» |

Behat в change не задействован: у проверок нет актора вне команды разработки
(`features/CLAUDE.md`, «Куда не класть»).

Итого строк: новая — 5, уже покрыта — 0, переписывается — 0, `БЛОКИРОВАНО` — 0,
`ТРЕБУЕТ РЕШЕНИЯ` — 0.

Сверка со спекой: сценариев 9, все названы. Строк 5, потому что три сценария
названы только прозой, а шесть разведены на строку и прозу (частичное покрытие).
Нормативных фраз без своего сценария — 2, обе названы ниже.

**Покрыто не своей строкой**
- «Старт dev без init», половина «нет `.env.local.php`»: строка «Тесты в dev идут
  в своём окружении», утверждение «окружение ядра — `test`». Дамп с
  `APP_ENV=dev` в корне при overload делает его `dev` (R2).
- Нормативная фраза требования «Dev-режим не использует дамп env» — «Phing
  читает цепочку `.env`-файлов». Уже покрыта:
  `tests/Unit/Tools/DotEnvPropertyTaskTest.php::testAppEnvTestReadsEnvChain`.

**Не покрывается вовсе** — инструмента в контуре нет: PHPUnit внутри php не видит
Compose и другие контейнеры (шов «шва нет» карты). Проверяется вручную задачей
верификации:
- «Первый старт demo», половина «приложение в php видит значения дампа» — шим
  и монтирование;
- «В php нет `.env`-файлов» (R4);
- «init завершился успешно» — порядок php и nginx;
- «init упал», половина «php не стартует» (R1);
- «Без .env init падает», половина «php не стартует»;
- «Значение из .env.local попадает в дамп», половина «приложение видит» —
  ручная задача 9.6;
- «Старт dev без init», половины «php стартует, не дожидаясь init» и
  «контейнер `init` не создаётся», и выключенный `init` в override (D6);
- «Тесты в dev идут в своём окружении», половина `e2e`: окружение
  Panther-сервера живёт в отдельном процессе, страница его не отдаёт;
- нормативная фраза «init … готовит БД и assets»: поведение Phing не меняется,
  автоматизации у него нет и сейчас.

### Решения

- **Вопрос 1 не задан**: ответ взят из `features/CLAUDE.md` — «Проверки сборки,
  phing-задач и изолированной логики → `tests/`: у них нет актора вне команды
  разработки». Демо запускает разработчик.
- **Вопрос 2 не задан**: у каждой строки жив один уровень. Скрипт дампа не
  касается ядра и БД, поэтому реального состояния для него нет. Окружение ядра
  без ядра не наблюдается.
- **Вопрос 7 не задан**: жив только вариант «всё как есть». Regression на R1 и R4
  наблюдать нечем (Compose вне контура). Regression на R2 требует отключить
  overload, а пользователь выбрал не создавать дамп в dev. R1 и R4 устраняются
  этим change и проверяются вручную.
- **Потолок наблюдения**: всё, что задаёт Compose (порядок, тома, монтирования),
  уходит в ручную верификацию: `docker compose -f docker-compose.yml config`,
  `docker compose config` и старт demo с намеренно битым `.env.prod.local`.
- Строка S7 фиксирует, что сейчас верно. Она краснеет при появлении
  `.env.local.php` с чужим `APP_ENV` в корне dev-проекта и тем закрывает R2
  для phpunit, не исправляя его.

### Предусловия

- **П1.** `tools/env-dump.php` принимает каталог dotenv и путь дампа аргументами
  (D3). Production-код. Разблокирует строки «Первый старт demo», «Правка env
  применяется перезапуском», «init упал».
- **П2.** Фикстуры-мини-проекты в `tests/Unit/Tools/fixtures/` (по образцу
  `DotEnvPropertyTaskTest`). Только тестовая обвязка. Разблокирует те же три строки.

## Наблюдаемость

Неприменима: change меняет только локальный demo-режим. Поведение production
(`docker-compose.deploy.yml`, CI, `secrets/env.php`) не меняется, и сигналов
в проде у change нет.

## Чувствительность проверок

| Строка | Правка, от которой проверка обязана покраснеть | Где видно |
|---|---|---|
| Первый старт demo и «Значение из .env.local попадает в дамп» (набор «без `.env.prod.local`, с `.env.local`») | скрипт не читает `.env.local`, читает его после `.env.prod` либо не читает `.env.prod` | вывод PHPUnit: нет `FIXTURE_LOCAL`, `FIXTURE_VALUE=from_local` или нет значения из `.env.prod` |
| Правка env применяется перезапуском (набор «старый дамп + `.env.prod.local`») | скрипт пишет дамп только при отсутствии файла либо не читает `.env.prod.local` | вывод PHPUnit: в дампе старое значение |
| init упал | скрипт перехватывает ошибку парсинга и выходит с кодом 0 | вывод PHPUnit: код возврата 0 |
| Без .env init падает (набор «нет `.env`») | скрипт добавляет `.env` в список только при наличии файла | вывод PHPUnit: код возврата 0 |
| Тесты в dev идут в своём окружении (утверждение «окружение ядра — `test`») | `.env.local.php` с `APP_ENV=dev` в корне проекта при `dotenv_overload: true` | вывод PHPUnit: окружение `dev` |

Существующие проверки, которые change обязан оставить зелёными и не удалять:
- `tests/Unit/Tools/DotEnvPropertyTaskTest.php` целиком;
- `tests/Unit/Command/GenerateSecretCommandBootEnvTest.php`;
- `tests/Integration/Command/GenerateSecretCommandConsoleTest.php`;
- `tests/E2E/HomepageTest.php`.
