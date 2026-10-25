# Tasks

## 1. Предусловия

- [x] 1.1 П1: создать `tools/env-dump.php <каталог-dotenv> <файл-дампа>` по design D3: явный список `.env` → `.env.prod` → `.env.prod.local` (если есть), парсер Symfony Dotenv, `var_export`-массив с `APP_ENV` из окружения процесса, ненулевой код возврата при ошибке. Проверка: `docker compose exec -T -e APP_ENV=prod php php tools/env-dump.php /srv build/env-dump-check.php` возвращает 0, `build/env-dump-check.php` возвращает массив с `APP_ENV=prod` и значениями `JWT_*` из `.env.prod`, без значений из `.env.local`
- [x] 1.2 П2: завести фикстуры-мини-проекты в `tests/Unit/Tools/fixtures/` по образцу `DotEnvPropertyTaskTest`: «без `.env.prod.local`, с посторонним `.env.local`», «старый дамп + `.env.prod.local` перекрывает `.env.prod`», «битый `.env`». Проверка: каталоги есть, каждый содержит только файлы своего набора

## 2. Тесты

- [x] 2.1 Скиллом `phpunit-tests` написать параметризованную проверку `EnvDumpScriptTest` (`tests/Unit/Tools/`, `TestCase`). Она покрывает строки «Первый старт demo» (набор «без `.env.prod.local`, с посторонним `.env.local`») и «Правка env применяется перезапуском» (набор «старый дамп + `.env.prod.local`»): запуск скрипта процессом, затем чтение произведённого дампа. Проверка: `make test tests/Unit/Tools/EnvDumpScriptTest.php` зелёный
- [x] 2.2 Скиллом `phpunit-tests` добавить в `EnvDumpScriptTest` отдельную проверку строки «init упал»: на наборе «битый `.env`» скрипт завершается с ненулевым кодом. Проверка: `make test tests/Unit/Tools/EnvDumpScriptTest.php` зелёный
- [x] 2.3 Скиллом `phpunit-tests` написать `tests/Integration/TestEnvironmentTest.php` (`KernelTestCase`, characterization) для строки «Тесты в dev идут в своём окружении» с утверждением «окружение ядра — `test`». Проверка: `make test-env tests/Integration/TestEnvironmentTest.php` зелёный

## 3. Чувствительность проверок

- [x] 3.1 Строка «Первый старт demo»: переключить сборку в скрипте на `loadEnv`, чтобы подмешивался `.env.local`. Убедиться, что `make test tests/Unit/Tools/EnvDumpScriptTest.php` краснеет именно на наборе «без `.env.prod.local`, с посторонним `.env.local`». Вернуть код, прогон снова зелёный
- [x] 3.2 Строка «Правка env применяется перезапуском»: в скрипте писать дамп только при отсутствии файла. Убедиться, что краснеет набор «старый дамп + `.env.prod.local`». Вернуть код, прогон снова зелёный
- [x] 3.3 Строка «init упал»: в скрипте перехватить ошибку парсинга и выйти с кодом 0. Убедиться, что краснеет проверка из 2.2. Вернуть код, прогон снова зелёный
- [x] 3.4 Строка «Тесты в dev идут в своём окружении»: положить в корень dev-проекта `.env.local.php` с `APP_ENV=dev`. Убедиться, что `make test-env tests/Integration/TestEnvironmentTest.php` краснеет с окружением `dev`. Удалить файл, прогон снова зелёный

## 4. Образ и Compose

- [x] 4.1 По design D7 создать в стадии `php` `Dockerfile` каталог `/run/dotenv` с владельцем `www-data` напрямую (`install -d`), без скилла `add-docker-volume`. Тому не нужны ни CI, ни права на сервере. Проверка: `docker compose -f docker-compose.yml build php`, затем `docker compose -f docker-compose.yml run --rm --no-deps --entrypoint stat php -c %U /run/dotenv` печатает `www-data`
- [x] 4.2 Создать шим `secrets/env-demo.php` (`return include '/run/dotenv/.env.local.php';`) по design D5. `secrets/env.php` не трогать. Проверка: `git diff --stat secrets/env.php` пуст, шим отличается от `secrets/env.php` только путём
- [x] 4.3 Изменить `docker-compose.yml` по design D4–D6:
  - том `dotenv`;
  - init: `dotenv` rw, шим, `.:/run/dotenv-src:ro`, команда `sh -c "php tools/env-dump.php /run/dotenv-src /run/dotenv/.env.local.php && exec phing"`, `depends_on: mysql: service_started`;
  - php: `dotenv:/run/dotenv:ro`, шим, без `.env`/`.env.prod`, `depends_on += init: service_completed_successfully`.

  Проверка: `docker compose -f docker-compose.yml config` показывает у php зависимость `init` с `service_completed_successfully`, а у php нет монтирований `.env`
- [x] 4.4 В `docker-compose.override.yml.dist` задать `php.depends_on: !override` (redis — `service_healthy`, mysql — `service_started`) и обновить локальную копию `docker-compose.override.yml`. Проверка: `docker compose config` проходит без ошибки `depends on undefined service "init"`, а `init` отсутствует в `docker compose config --services`

## 5. Документация

- [x] 5.1 Написать ADR `docs/adr/0005-…` в формате существующих ADR по design D1: в dev нет дампа, `dotenv_overload` не отключён, отвергнутые варианты B и C. Проверка: файл есть, на него ссылается `docs/runbooks/dotenv.md`
- [x] 5.2 Обновить `docs/runbooks/dotenv.md`: раздел про demo (init → дамп в томе → шим, правка `.env*` применяется перезапуском, `.env.prod.local` как аналог CI) и миграция dev-override. Проверка: в runbook нет утверждений, что demo-php монтирует `.env`
- [x] 5.3 Обновить `README.md` (запуск demo, копирование override после обновления) и строку `tools/` в корневом `CLAUDE.md`, где добавляется сборка дампа env. Проверка: `rg -n "env-dump|dotenv" README.md CLAUDE.md` находит новые упоминания

## 6. Ручная верификация Compose-сценариев

- [x] 6.1 «Первый старт demo», «init завершился успешно», «В php нет `.env`-файлов». После `docker compose down` выполнить `docker compose -f docker-compose.yml up -d`. Проверка:
  - `docker compose -f docker-compose.yml ps -a`: init `exited (0)`, php и nginx запущены после него;
  - в выводе `docker compose -f docker-compose.yml exec php ls -a /srv` из `.env*` есть только `.env.local.php`;
  - `docker compose -f docker-compose.yml exec php php -r 'var_export((include "/srv/.env.local.php")["APP_ENV"]);'` печатает `'prod'`
- [x] 6.2 «Правка env применяется перезапуском»: создать `.env.prod.local` с `ENV_default_uri=http://demo.check` и перезапустить demo. Проверка: та же команда `php -r` для `ENV_default_uri` печатает новое значение, дамп руками не удалялся. Затем удалить `.env.prod.local`
- [x] 6.3 «init упал»: создать `.env.prod.local` с синтаксической ошибкой и запустить demo. Проверка: `docker compose -f docker-compose.yml up -d` завершается ошибкой, init `exited` с ненулевым кодом, php не запущен. Затем удалить `.env.prod.local`
- [x] 6.4 «Старт dev без init»: `docker compose down`, затем `make up`. Проверка: php запущен, init не создавался, в корне проекта нет `.env.local.php`; `make test-env` и `make behat` зелёные

## 7. Выключенный init в dev

- [x] 7.1 По design D6 в `docker-compose.override.yml.dist` у `init` убрать `user: "1001:1001"`, задать `profiles: ["disabled"]` и `command: ["false"]`, обновить локальную копию `docker-compose.override.yml`. Проверка: `docker compose config --services` не содержит `init`; в `docker compose --profile disabled config init` команда — `false`, `user` нет
- [x] 7.2 «Старт dev без init»: `docker compose down`, затем `make init`. Проверка: `make init` зелёный, `docker compose ps -a` не показывает `init`, в корне проекта нет `.env.local.php`
- [x] 7.3 Случайный запуск `init` в dev: `docker compose --profile disabled run --rm --no-deps init`. Проверка: код возврата ненулевой, контейнер ничего не выполнил

## 8. Документация: обоснования в ADR

- [x] 8.1 По design D8 дополнить ADR 0005 обоснованиями demo-механики: пересборка дампа при каждом старте в томе `dotenv`; `tools/env-dump.php` вместо `bootEnv` (прочитал бы прежний дамп через шим), `loadEnv` (подмешал бы `.env.local`), `symfony:dump-env` и `dotenv:dump` (нет composer, `/srv` read_only); цепочка `.env` → `.env.prod` → `.env.prod.local` как в CI; шим как `secrets/env.php`; `phing` после дампа и php после успешного `init`; выключенный `init` в dev с заглушкой `false`. В Consequences — перезапуск через `down` + `up -d`. Проверка: у каждого «почему» из `docs/runbooks/dotenv.md` и `README.md` есть пункт в ADR
- [x] 8.2 `docs/runbooks/dotenv.md`: абзац «`.env.local.php` в dev не создавать» сократить до запрета и ссылки на ADR 0005; в разделе demo оставить шаги без обоснований, ссылку «Почему так — ADR 0005» и перезапуск `docker compose down` + `docker compose up -d`; ловушку про override переписать под профиль `disabled`. Проверка: в runbook нет пересказа довода «overload → чужой `APP_ENV`», есть ссылки на ADR 0005 и команда перезапуска
- [x] 8.3 `README.md`: абзац о demo сократить до команды, одной фразы («`init` собирает дамп env и выполняет `phing`, php стартует после него») и ссылки на runbook; строку `docs/runbooks/dotenv.md` в таблице документации привести к `docs/CLAUDE.md` («Dotenv: цепочка env, demo-дамп, prod-шим»). Проверка: `rg -n "env.prod.local" README.md` пуст
- [x] 8.4 В docblock `tests/Integration/TestEnvironmentTest.php` заменить пересказ довода ссылкой на ADR 0005. Проверка: `make test-env tests/Integration/TestEnvironmentTest.php` зелёный

## 9. Цепочка с `.env.local`

- [x] 9.1 Фикстуры: набор `env-dump-chain` переописать — `.env.local` теперь часть цепочки, а не посторонний файл; добавить набор `env-dump-no-dotenv` с `.env.local` и `.env.prod`, без `.env`. Проверка: каталог `env-dump-no-dotenv` есть, `.env` в нём нет
- [x] 9.2 Скиллом `phpunit-tests` в `EnvDumpScriptTest`: для набора `env-dump-chain` ожидать `FIXTURE_LOCAL=from_local` и `FIXTURE_VALUE=from_prod`, поправить имя набора, комментарии и docblock под цепочку design D3; добавить проверку строки «Без .env init падает» на наборе `env-dump-no-dotenv` — ненулевой код возврата. Проверка: `make test tests/Unit/Tools/EnvDumpScriptTest.php` краснеет на наборе `env-dump-chain`
- [x] 9.3 По design D3 в `tools/env-dump.php` добавить `.env.local` (если есть) между `.env` и `.env.$env`, поправить комментарий в шапке. Проверка: `make test tests/Unit/Tools/EnvDumpScriptTest.php` зелёный
- [x] 9.4 Чувствительность, каждая правка с возвратом кода и зелёным прогоном после:
  - убрать `.env.local` из списка — краснеет набор `env-dump-chain`;
  - поставить `.env.local` после `.env.prod` — краснеет набор `env-dump-chain`;
  - добавлять `.env` в список только при наличии файла — краснеет проверка «Без .env init падает».
- [x] 9.5 Документация: в ADR 0005 добавить `.env.local` в цепочку, убрать «`.env.local` в demo не читается», причину отказа от `loadEnv` заменить на фолбэк `.env.dist`; в `docs/runbooks/dotenv.md` поправить шаг 1 demo. Проверка: `rg -n "не читается" docs/adr/0005-env-dump-only-in-demo.md docs/runbooks/dotenv.md` пуст
- [x] 9.6 «Значение из .env.local попадает в дамп»: в `.env.local` задан `ENV_database_password`; выполнить `docker compose down -v --rmi local`, затем `docker compose -f docker-compose.yml up -d`. Проверка: `docker compose -f docker-compose.yml exec php cat /run/dotenv/.env.local.php` содержит значение из `.env.local`, init `exited (0)`

## 10. Завершение (выполняет пользователь)

- [x] 10.1 Пользователь запускает `make qa`. Проверка: все цели зелёные
- [x] 10.2 Пользователь проводит `code-review` изменений change'а. Проверка: замечания закрыты или вынесены в отдельные задачи
