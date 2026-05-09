# Changelog

Все заметные изменения в этом проекте будут задокументированы в этом файле.

Формат файла основан на [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/),
и этот проект придерживается [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

<!-- Изменения в шаблоне Symfony backend: -->

### Added

- **Redis 8 как хранилище сессий**: сервис `redis:8-trixie` в **`docker-compose.yml`** и **`docker-compose.deploy.yml`** (`requirepass` через `${ENV_redis_password}` на deploy, healthcheck, persistence через volume `redis-data`, `read_only` + `cap_drop: ALL` + `security_opt: no-new-privileges:true`, `tmpfs:/tmp`); `php8.4-redis` добавлен в **`Dockerfile`** и `ext-redis: *` — в **`composer.json`**. В **`config/packages/framework.yaml`** подключён `Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler` (с `prefix: 'sf_s:'`); в **`config/services.yaml`** сервис `Redis` собирается через `Symfony\Component\Cache\Adapter\RedisAdapter::createConnection(%env(REDIS_URL)%)`, дефолты `env(REDIS_URL)` и `env(ENV_redis_*)` оставлены для CI-окружения. В **`.env`** добавлены `ENV_redis_host`, `ENV_redis_port`, `ENV_redis_password`. В **`.gitlab-ci.yml`** у `qa:behat` поднимается дополнительный сервис `redis:8-trixie` (alias `redis`, пароль в CI — **`root`**, см. **Changed**); в скрипте `deploy-mainline:secure-copy` шаг `docker compose up -d --no-recreate mysql` дополнен `redis`, чтобы Redis был доступен до пересоздания `spare`/`php`. Fail-fast проверки секретов GitLab для mainline — в **Changed** («GitLab CI — `prepare-mainline:env`»). Описание стека и шага — **`README.md`**, **`ABOUT.md`**, [`docs/00-symfony.md`](docs/00-symfony.md), [`docs/symfony/init.md`](docs/symfony/init.md), [`DEPLOYMENT.md`](DEPLOYMENT.md).
- **Phing-задача `symfonyenv` — зеркало переменных Symfony Dotenv** ([`tools/dotEnvPropertyTask.php`](tools/dotEnvPropertyTask.php), новый [`tools/phing-bootstrap.php`](tools/phing-bootstrap.php), задача переименована в `symfonyenv` через `<taskdef>` в **`build.xml`**). Bootstrap из `<autoloader>` читает Phing-свойства `-Dapp.env=...`/`-Dapp.debug=...`, выставляет соответствующие `$_SERVER['APP_ENV']`/`$_SERVER['APP_DEBUG']` и вызывает `Symfony\Component\Dotenv\Dotenv::bootEnv()` так же, как Symfony Runtime для приложения — поэтому `.env.local.php` (создаваемый `composer dump-env <env>` для прод-артефакта) подхватывается одинаково в Phing и в приложении. После `bootEnv` в `$_SERVER['SYMFONY_DOTENV_VARS']` добираются `APP_ENV`/`APP_DEBUG` (`bootEnv` сама их в этот список не вносит, если они уже были в `$_ENV` до её вызова), чтобы свойства `${dotenv.APP_ENV}`/`${dotenv.APP_DEBUG}` всегда были доступны в `build.xml`. Сам Phing-таск `symfonyenv` копирует имена из `SYMFONY_DOTENV_VARS` в свойства проекта с префиксом `dotenv` (значения берутся из `$_SERVER`, без `getenv`/`putenv`). Описание стека и шага — [`docs/00-symfony.md`](docs/00-symfony.md), [`docs/symfony/init.md`](docs/symfony/init.md).

### Changed

- **Загрузка `.env` через Symfony Runtime**: убран ручной вызов `Dotenv::loadEnv` из **`bin/console`** и **`public/index.php`** — переменные подставляет только **`symfony/runtime`**; в **`composer.json`** в `extra.runtime` добавлено **`dotenv_overload: true`**, чтобы локально значения из **`.env.local`** перекрывали уже заданные в окружении. В **`tests/bootstrap.php`** вместо `loadEnv(..., 'test', 'test', ...)` используется **`Dotenv::bootEnv()`** для согласованной загрузки цепочки env-файлов в PHPUnit.

- **Секреты приложения и JWT в конфигурации**: в **`.env`** `ENV_secret` переименован в **`ENV_app_secret`**, добавлен **`ENV_jwt_passphrase`**; шаблонные блоки Flex для Doctrine/Lexik в **`.env`** убраны — пути к ключам JWT заданы в **`config/packages/lexik_jwt_authentication.yaml`** как **`%kernel.project_dir%/config/jwt/private.pem`** и **`public.pem`**, passphrase — **`%env(ENV_jwt_passphrase)%`**. В **`docs/symfony/init.md`** обновлено описание переменных приложения.

- **`docker-compose.yml` (локальный dev)**: для сервиса **Redis** пароль в **`command`** и **`REDISCLI_AUTH`** зафиксирован литералом **`root`**, чтобы совпадать с дефолтами в **`.env`** и с Redis в job **`qa:behat`**.

- **`docker-compose.deploy.yml`**: для **`php`** / **`spare`** снова используются **`env_file: [.env, .env.local]`** (временный откат после эксперимента с явным блоком `environment` без файлов — конфигурация на сервере снова подхватывается из сгенерированных при деплое файлов).

- **GitLab CI — `prepare-mainline:env`**: списки **`REQUIRED_VARS`** и **`REQUIRED_FILES`** оформлены многострочным YAML; добавлены проверки **`ENV_app_secret`**, **`ENV_database_password`**, **`ENV_jwt_passphrase`** и файловых переменных **`JWT_PRIVATE_KEY`**, **`JWT_PUBLIC_KEY`**; **`SSH_PRIVATE_KEY`** перенесён в **`REQUIRED_FILES`** (тип **File** в GitLab Variables); сообщения об ошибках уточнены; убран временный **`exit 1`** после проверок.

- **GitLab CI — деплой по SSH**: шаблон **`.deploy-script-ssh`** — **`ssh-add`** получает путь из File-переменной **`SSH_PRIVATE_KEY`**, перед этим **`chmod 600 "$SSH_PRIVATE_KEY"`** (требования OpenSSH к правам на приватный ключ).

- **GitLab CI — `qa:behat`**: у сервиса Redis в **`services`** параметр **`--requirepass`** приведён к **`root`**, удалены **`variables: REDISCLI_AUTH`** у этого сервиса.

- **`public/index.php`**: удалён неиспользуемый **`use Symfony\Component\Dotenv\Dotenv`**.

- **`build.xml`**: вместо двух `<dotenvproperty file=".env"/>` + `<dotenvproperty file=".env.local" override="true"/>` теперь единственный `<symfonyenv prefix="dotenv" />`; удалены свойства `env.file` / `env.local.file`, отладочный `<target name="tmp">`; все ссылки `${docker-compose.*}` приведены к `${dotenv.*}`. Атрибуты `file`/`override` у задачи больше не нужны (см. **Removed**).

- **`Makefile`**, **`.gitlab-ci.yml`** (`qa:behat`, `deploy-mainline:secure-copy`), **`Dockerfile`** (`RUN phing app-deploy`): прежние флаги `-Dsymfony.env=...` / `-Denv.local.file=...` заменены на единственный **`-Dapp.env=...`** (`-Dapp.debug=true|false` — опционально). Подключение `.env.local.php` / `.env.test` теперь определяет сам `bootEnv` по тем же правилам, что и Symfony Runtime в приложении.

### Removed

- Сессии в MySQL: удалены миграция **`migrations/Version20210806095751.php`** (создание таблицы `sessions`) и сервис `PdoSessionHandler` в **`config/packages/framework.yaml`**. Хранение сессий полностью переехало в Redis; в шаблонном репозитории «двушаговая» история (создать → удалить таблицу) не имеет смысла, поэтому миграция удалена напрямую, а таблица `sessions` чистится вручную на действующих стендах (см. [`docs/symfony/init.md`](docs/symfony/init.md)).

## [0.0.8-alpha]

### Changed

- **`Makefile`**: цель **`behat`** — **`docker compose exec -T`** для шагов Phing и Behat (без псевдо-TTY, удобно для CI и агентов); **`vendor/bin/behat`** вызывается с **`--no-interaction`**, чтобы Behat не ждал ввода (сниппеты и т.п.); в Makefile добавлены поясняющие комментарии.
- **`.cursor/skills/behat-tests/SKILL.md`**, **`.cursor/skills/phpunit-tests/SKILL.md`**: для прямого **`docker compose exec`** в неинтерактивном режиме зафиксировано использование **`exec -T`** (как в целях **`make behat`** / **`make test`**); в примерах Behat добавлен флаг **`--no-interaction`**.
- **`tests/bootstrap.php`**: в **`Dotenv::loadEnv`** включено переопределение уже заданных переменных окружения значениями из **`.env`** (последний аргумент **`true`**), чтобы PHPUnit-прогон был предсказуемее при наследовании env от хоста или CI.

## [0.0.7-alpha]

### Changed

- **`README.md`**: раздел **«Эксперимент с файловой системой»** заменён на **«Добавление новых volume»** со ссылкой на [`docs/symfony/volume.md`](docs/symfony/volume.md) и промптом для агента Cursor. Эксперимент с **`read_only: true`** для **`php`**/**`nginx`**/**`spare`** считается успешно завершённым; режим оставлен включённым, отдельный раздел про него больше не нужен.

## [0.0.6-alpha]

### Changed

- **`Dockerfile`**: из образа убран **`ENV HOME=/srv`** — при локальной разработке домашний каталог совпадал с **`/srv`** и томом проекта, из‑за чего **`~/.config`**, **`~/.cache`** и т.п. появлялись в корне репозитория на хосте.

- **`docker-compose.yml`**: у сервиса **`php`** задано **`environment: HOME: /tmp`**, чтобы пользовательские конфиги и кэши процессов в контейнере уходили на **`tmpfs`** (**`/tmp`**) и не засоряли смонтированный **`./:/srv`**.

- **`.gitlab-ci.yml`**: у шаблона **`.test-script`** добавлено **`variables: HOME: /srv`**, чтобы **`composer config -g`** и глобальный конфиг Composer в job выполнялись относительно рабочего каталога **`/srv`** (`www-data`), без ошибки записи в **`/var/www/.config`**.

## [0.0.5-alpha]

### Changed

- Контейнер **nginx**: в **dev** (`docker-compose.yml`) и в **deploy** (`docker-compose.deploy.yml`) включён **`read_only: true`** для корня ФС контейнера; **`cap_drop: [ALL]`**, **`security_opt: no-new-privileges:true`**, **`tmpfs`** на **`/tmp`** (`rw`, `noexec`, `nosuid`; в dev у **`/tmp`** задан лимит **128M**, в deploy — без явного лимита). Логи базового образа через stdout/stderr.

- Контейнеры **`php`** и **`spare`**: в **dev** (`docker-compose.yml`) у **`php`** включён **`read_only: true`**, **`cap_drop: [ALL]`**, **`security_opt: no-new-privileges:true`**, **`tmpfs`** на **`/tmp`**; **`cap_add`** не используется (при **`user:`** в override мастер и воркеры под одним uid). В **deploy** (`docker-compose.deploy.yml`) то же для **`php`** и **`spare`** (якорь **`&php-service`**).

- **Эксперимент read-only:** режим **`read_only`** для **`php`** (локально и на сервере) и для **nginx** на deploy рассматривается как пробный; при ошибках записи в образ нужны отдельные **`tmpfs`** или **`volumes`**. В **`README.md`** добавлен раздел **«Эксперимент с файловой системой»** — последствия для **`php`**, куда писать при read-only и как различать **`tmpfs`** и тома.

- **`Dockerfile`** (образ приложения / CI): **`ENV HOME=/srv`** (глобальный конфиг Composer и пр. не пишут в `/var/www/.config` под **`www-data`**); **`chown www-data:www-data /srv`** перед **`WORKDIR /srv`**; стейдж **`source`**: **`COPY --chown=www-data:www-data`** для зависимостей и кода, стейдж **`prod`**: **`COPY --from=source_image --chown=www-data:www-data`**, чтобы сборка и runtime под **`www-data`** согласованы с пайплайном.

- **`README.md`**: в «Быстрый старт» добавлено копирование **`docker-compose.override.yml.dist`**; новый раздел **«Начало работы»** про поле **`user`** и **`make owner`**; из «Принципов…» убран дублирующий блок про владельца файлов, удалён отдельный пункт про цикл миграций/схему БД, сокращены отсылки в пункте про команды в контейнере **`php`**.

- **`docker-compose.override.yml.dist`**: **`XDEBUG_CONFIG`** с **`client_host=host.docker.internal`** и **`extra_hosts: host.docker.internal:host-gateway`** для Linux Docker Engine.

## [0.0.4-alpha]

### Added

- `.cursor/rules/vendor-class-lookup.mdc` (опциональное правило, `alwaysApply: false`): как агенту разрешать FQCN к файлам **без** поиска определений в интернете и **без** рекурсивного обхода `vendor/` — только по артефактам Composer (`autoload_classmap.php` / при пустоте `autoload_psr4.php`, при необходимости `composer.lock`). Учтены классы приложения: префиксы `App\` / `App\Tests\` и `composer.json` (`autoload` / `autoload-dev`, корни `src/`, `tests/`), пути в classmap через `$baseDir`; отдельно зафиксировано, что новый класс в `src/` может **не попасть** в classmap до `composer dump-autoload`, но путь задаётся PSR-4. В начале — краткий алгоритм (§1 → §2 → §3), **Human in the Loop** при отсутствии символа или файла по карте; терминальный `grep` по абсолютным путям к файлам под `vendor/composer/`, т.к. индекс workspace часто не видит `vendor/`.

### Changed

- Документация: обновлён `DOCUMENTATION.md` (три среза уровней, вершины `README.md` и `DEPLOYMENT.md`, правила ссылок в `docs/`); наполнены `docs/00-symfony.md` (этап Symfony: что установлено, стек, основные бандлы, ссылка на шаг) и `docs/symfony/init.md` (локальный цикл, QA, PHPUnit, Behat, переменные окружения — перенос из `README.md`); `README.md` сокращён до точки входа для разработчика; заготовка `docs/symfony/template.md` заменена на `docs/symfony/init.md`. Содержимое `DEPLOYMENT.md` в `docs/` в этой итерации не переносилось.

## [0.0.3-alpha]

### Changed

- Always-applied правило `.cursor/rules/skills.mdc` переименовано в `.cursor/rules/run-php-in-docker.mdc` (содержимое без изменений); обновлены ссылки в `ABOUT.md`, `.cursor/agents/documenter.md`, `.cursor/rules/update-changelog.mdc`, `.cursor/skills/update-rules/SKILL.md`, `.cursor/skills/brainstorming-workflow/SKILL.md`; в `CHANGELOG.md` в списке **Затронутые пути** для `[0.0.2-alpha]` путь к этому правилу приведён к новому имени.
- `.cursor/rules/update-changelog.mdc`: явные триггеры «когда применять», отсылка к `.cursor/skills/update-rules/SKILL.md`, `globs` на `src/`, `tests/`, `config/`, `migrations/`, `public/` для чащего подключения правила при правках кода.

## [0.0.2-alpha]

### Added

- `.cursor/skills/run-php-in-docker/SKILL.md`: единый скилл для агента — запуск Symfony Console, Composer, PHPUnit, Behat, Phing, PHP CS Fixer, PHPStan, Rector только в контейнере `php`, приоритет `make` и `README.md`; подробности в `README.md`.
- `tests/Serialization/RestApiSerializationKernelTest.php`: интеграционные тесты JSON-формы REST-объектов через `serializer` и группу `api` (согласовано с FOSRest `fos_rest.serializer.groups`).
- Подготовка окружения для Behat с доступом к MySQL: цель `make behat` перед `vendor/bin/behat` вызывает Phing с `-Dsymfony.env=test` и `-Denv.local.file=.env.test` (согласовано с job `qa:behat` в GitLab CI).
- `build.xml`: свойства `env.file` и `env.local.file`, чтобы подставлять нужные файлы окружения при загрузке переменных для Phing/Docker Compose.
- `.env.test`: `ENV_database_name=db_name_test` для имени тестовой БД в связке с Compose/CI.
- GitLab CI (`qa:behat`): сервис `mysql:8.0-debian` (alias `mysql`, `MYSQL_ROOT_PASSWORD`), подготовка через `phing` вместо копирования `.env.test` в `.env.local`.
- `src/Controller/IndexController.php`: маршрут `/` с пустым ответом — базовый HTTP-эндпоинт для сценариев.

### Changed

- Скилл оркестрации planner → worker → reviewer → documenter: каталог `.cursor/skills/subagent-workflow/` переименован в `.cursor/skills/brainstorming-workflow/` (`name` во frontmatter `brainstorming-workflow`), ссылка в `.cursor/rules/php-map-and-test.mdc` обновлена.
- `.cursor/rules/skills.mdc` (переименован из `general-rules.mdc`): always-applied правило сфокусировано на инструкции использовать проектные Cursor skills для типовых операций; перечень skills (`update-rules`, `run-php-in-docker`, …) и самоссылка при сопровождении — в этом файле; обновлены `.cursor/skills/update-rules/SKILL.md`, `.cursor/skills/brainstorming-workflow/SKILL.md`, `.cursor/agents/documenter.md`.
- `ABOUT.md`: цель шаблона — подробности для людей в `README.md`, для агента — перечень skills в `.cursor/rules/skills.mdc` без дублирования README; комментарий сопровождения шаблона — с учётом `skills.mdc`.
- `.cursor/skills/run-php-in-docker/SKILL.md`, `README.md`, `ABOUT.md`, `.cursor/skills/phpunit-tests/SKILL.md`, `.cursor/skills/behat-tests/SKILL.md`: скилл снова компактный со ссылками на `README.md` для подробностей; откат эксперимента с «только односторонними» ссылками на скилл; описание в YAML правила skills — краткое, как раньше.
- REST-сериализация переведена на **Symfony Serializer**: `fos_rest.service.serializer: fos_rest.serializer.symfony`, `fos_rest.serializer.groups: ['api']`; `framework.serializer.enabled: true`. JMS-атрибуты на сущностях и `App\Model\Rest\*` заменены на `Symfony\Component\Serializer\Attribute` (`Groups`, `Ignore`).
- `composer.json`: зависимость `jms/serializer-bundle` удалена; добавлена явная зависимость `symfony/serializer` (`7.4.*`).
- `.cursor/rules/php-map-and-test.mdc`, `.cursor/rules/php-new-implementation-testing.mdc`: тексты правил переведены на русский язык (включая поле `description` в заголовках YAML); во втором правиле ссылка на парное правило приведена к фактическому имени файла `php-map-and-test.mdc`; добавлена отсылка к скиллу `.cursor/skills/phpunit-tests/SKILL.md` для раскладки тестов и запуска в Docker.
- `.cursor/rules/php-map-and-test.mdc`: для крупных многошаговых задач добавлена отсылка к скиллу `.cursor/skills/brainstorming-workflow/SKILL.md` (оркестрация planner → worker → reviewer → documenter, HITL).
- `config/packages/test/doctrine.yaml`: `dbname_suffix` без литерала `_test` — только `%env(default::TEST_TOKEN)%` (имя тестовой БД задаётся через окружение).
- `tests/bootstrap.php`: явно `APP_ENV=test`, загрузка `.env` через `Dotenv::loadEnv(..., 'test', 'test')`, без подключения `config/bootstrap.php`.

**Затронутые пути:** `ABOUT.md`, `.cursor/agents/documenter.md`, `.cursor/rules/run-php-in-docker.mdc`, `.cursor/skills/behat-tests/SKILL.md`, `.cursor/skills/phpunit-tests/SKILL.md`, `.cursor/skills/run-php-in-docker/SKILL.md`, `.cursor/skills/brainstorming-workflow/SKILL.md`, `.cursor/skills/update-rules/SKILL.md`, `.env.test`, `.gitlab-ci.yml`, `Makefile`, `bin/console`, `build.xml`, `config/packages/test/doctrine.yaml`, `public/index.php`, `src/Controller/IndexController.php`, `tests/bootstrap.php`.

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
