# Design

## Context

Мотивация — `proposal.md`, раздел Why; требования — `specs/items`, `specs/health`.

Что в проекте уже есть и что определяет подход:

- Сервис `Redis` (`config/services.yaml`) создаётся через
  `RedisAdapter::createConnection('%env(REDIS_URL)%')`, приватный, им же
  пользуется `RedisSessionHandler`. Соединение устанавливается при создании
  сервиса, а не при первой команде.
- Атрибутный роутинг сканирует `src/Controller/`; `src/Controller/Rest/`
  дополнительно получает префикс `/api` и попадает под JWT-firewall `api`.
  Пути вне `/api` обслуживает firewall `main` с `security: false`.
- FOSRest: `view_response_listener: force` сериализует возвращаемый
  контроллером объект с группой `api`; `exception` + `serializer_error_renderer`
  рендерят исключения в формате запроса; `body_listener` раскладывает JSON-тело
  в `$request->request`. Формат запроса выбирает `format_listener` по правилам
  путей; для `^/` правило отдаёт `html`.
- Модели ответов — `App\Model\Rest\*` (`Success`, `AuthToken`): поля
  в конструкторе, `#[Groups(['api'])]`, геттеры.

Раздела «Brownfield-поведение» нет: существующее поведение change не меняет.
Пути `/items`, `/items/{id}`, `/health` сейчас отвечают 404; новое правило
`format_listener` действует только на них; правки `qa`, `openspec/config.yaml`
и документации — формулировки, а не поведение.

## Goals / Non-Goals

**Goals:**

- Контроллер на ручку, по одному классу с `__invoke`; поведение — строго по specs.
- Валидация тела одна на POST и PUT, без дублирования в контроллерах.
- Ответы и ошибки — через существующий конвейер FOSRest, без ручной сборки
  `JsonResponse`.
- Тесты не трогают данные dev-окружения в Redis.

**Non-Goals:**

- Метрики, `/metrics`, `/handlers`, Prometheus и Grafana — части 1–2 HW1,
  отдельные changes.
- Удаление записей, листинг, TTL.
- Защита от гонки при одновременной замене одной записи: последний `PUT`
  выигрывает.
- Описание `/items` и `/health` в Nelmio: область документации ограничена `^/api`.

## Decisions

### D1. Контроллеры и роуты

`src/Controller/Item/CreateItemController`, `GetItemController`,
`UpdateItemController` и `src/Controller/HealthController`. Каждый —
`final` класс с единственным публичным `__invoke`; роут — атрибутом на
`__invoke` с явным именем: `items_create`, `items_get`, `items_update`, `health`.
У `{id}` требование `[1-9]\d{0,17}` — константа `ItemRoute::ID_REQUIREMENT`
(`src/Controller/Item/`, забота маршрутизации, а не модели ответа) на роуты
`items_get` и `items_update`: нечисловой id, `0`, `01` и id длиннее 18 цифр
не совпадают с роутом и дают 404 от роутера — для `PUT` раньше проверки тела,
как и требует spec («Замена значения записи»). Ограничение длины нужно потому,
что строка за пределами `PHP_INT_MAX` не приводится к `int $id` контроллера:
`TypeError` дал бы 500. 18 цифр меньше `PHP_INT_MAX`, а `items:seq` до них
не дорастёт.

404 «записи нет» у `GetItemController` и `UpdateItemController` — одно
исключение `App\Controller\Item\ItemNotFoundHttpException`
(`NotFoundHttpException` с id в сообщении), а не два одинаковых `throw`.

Явные имена роутов понадобятся части 1–2 (метка ручки и `GET /handlers`):
шаблон пути берётся из роута по имени, а не из фактического URL.

Базовый класс `AbstractFOSRestController` не нужен: контроллерам не нужны его
помощники, а сериализацию возвращаемого объекта делает
`view_response_listener`. Код `201` для `POST` задаёт атрибут
`#[Rest\View(statusCode: 201)]`.

*Альтернатива:* один класс `ItemController` с тремя методами — отвергнута
требованием «отдельный класс с `__invoke`».

### D2. JSON для `/items` и `/health` — правилом `format_listener`

В `config/packages/fos_rest.yaml` перед правилом `^/` добавляется
`{ path: ^/(items|health)(/|$), prefer_extension: false, fallback_format: json, priorities: [ json ] }`.
Путь закрыт справа: иначе правило забрало бы и `/healthz`, `/items-foo`.
Правило перечисляет JSON-ручки вне `/api`: новая такая ручка дописывается
в него, о чём говорит комментарий у правила.

*Почему не `defaults: { _format: json }` на роутах:* `FormatListener` FOSRest
висит на `kernel.request` с приоритетом 34, `RouterListener` — с 32. Формат
выбирается до того, как роутер положит `_format` в атрибуты, и для `Accept: */*`
правило `^/` успевает выставить `html`. Тогда объект ответа и исключения
рендерятся как HTML. Правило пути — единственное место, где формат решается
вовремя; так же устроен `^/api`.

### D3. Модели ответов

- `App\Model\Rest\ItemId` — `int $id`; ответ `POST` и `PUT`.
- `App\Model\Rest\ItemValue` — `string|int|float|bool $value`; ответ `GET`
  и одновременно разобранное тело запроса (D5). Обе модели — транспортные:
  в хранилище (D4) они не идут.
- `/health` отвечает существующей `App\Model\Rest\Success('ok')`.

Две модели вместо одной `Item` с nullable `id`: при `serialize_null: true`
общая модель дала бы `"id": null` в ответе `GET`, а specs требуют ровно один
ключ.

### D4. Хранилище — `App\Repository\ItemRepository` поверх `\Redis`

| Операция | Команды Redis | Результат |
|---|---|---|
| создать | `INCR items:seq` → `N`; `SET item:N '{"value":X}'` | `N` |
| прочитать | `GET item:N` → `json_decode` | значение или `null` |
| заменить | `SET item:N '{"value":X}' XX` | `false`, если ключа нет |

- `INCR` атомарен, поэтому id уникальны и между экземплярами сервиса с общим
  Redis. Неудачный `SET` после `INCR` оставляет дыру в нумерации — specs
  требуют уникальности, а не непрерывности.
- `SET … XX` пишет только в существующий ключ: проверка существования и замена —
  одна команда, без окна между `EXISTS` и `SET`, и `PUT` не создаёт запись.
- Значение хранится JSON-строкой `{"value":X}`: JSON сохраняет тип (`1` и `"1"`
  различимы), HASH превратил бы всё в строки.
- Интерфейс — скаляры: значение `string|int|float|bool` и `int` id.
  `ItemValue` и `ItemId` — модели ответа (D3), их собирают резолвер
  и контроллеры. Отдельный тип для id не заводится: в хранилище id — часть
  ключа Redis, а `ItemId` — транспортная модель.
- Схема ключей описана в `ItemRepository`; runbook `redis.md` ссылается
  на него как на источник правды.

*Имя и место:* `Repository` — по роли (доступ к записям), хотя репозитории
в каталоге до сих пор доктриновские. Отдельный каталог `Storage/` для одного
класса не заводится.

### D5. Разбор и валидация тела — `ValueResolver` для `ItemValue`

`App\ValueResolver\ItemValueResolver` (`ValueResolverInterface`) срабатывает
на аргумент контроллера типа `ItemValue`. Он берёт `$request->request->all()`
(JSON уже разобран `body_listener`'ом) и требует: media type заголовка
`Content-Type` (без параметров, без учёта регистра) — ровно `application/json`,
ключи ровно `['value']`, `is_scalar($value)`, а для `float` —
`is_finite`. Иначе — `BadRequestHttpException`, которая через FOSRest
становится JSON-ответом 400. Невалидный JSON отклоняет сам `body_listener`
(тоже 400).

Проверка `Content-Type` нужна потому, что `body_listener` пропускает тела
форм, а Symfony сам раскладывает их в `$request->request`: без неё
`value=foo` формой давал бы 201. Формат `json` из `getContentTypeFormat()`
для этого не годится: в него входит и `application/x-json`, а spec допускает
только `application/json`. Проверка `is_finite` нужна потому, что
`{"value": 1e400}` декодируется в `INF`: `is_scalar` его пропускает,
а `json_encode` в хранилище бросил бы исключение и дал 500.

Резолвер работает на этапе разрешения аргументов, до вызова контроллера.
Поэтому `PUT` с плохим телом отвечает 400, не обращаясь к Redis, — как
и требует spec (проверка тела не зависит от существования записи).

*Альтернативы:*
- `#[MapRequestPayload]` — по умолчанию отвечает 422, а не 400; запрет лишних
  ключей и `null` требует настройки сериализатора и констрейнтов, это больше
  кода, чем десяток строк резолвера.
- Проверка в каждом контроллере — дублирование между POST и PUT.

### D6. `/health`: `PING` с ленивым получением соединения

`HealthController` получает Redis не объектом, а замыканием
(`#[AutowireServiceClosure(\Redis::class)]`) и вызывает его внутри `try`.
Причина: `createConnection` подключается при создании сервиса. Если внедрить
`\Redis` в конструктор, падение подключения случится при сборке контроллера,
вне его `try`, и ответ будет 500, а не 503. Исключение подключения или `PING`
→ `ServiceUnavailableHttpException` → JSON 503.

Отказ подключения `RedisAdapter::createConnection` сообщает исключением Cache
`InvalidArgumentException`, хотя его docblock обещает его только для
невалидного DSN. Контроллер опирается на это поведение, а не на контракт:
комментарий у `catch` говорит об этом, а изменение поведения при обновлении
`symfony/cache` поймает набор `connect-fails`.

Ручки `/items` при недоступном Redis отвечают 500 — specs этого случая не
описывают, а для части 3 HW1 это естественный источник 5xx.

### D7. Изоляция тестов в Redis

В test-окружении `REDIS_URL` указывает на отдельную БД Redis (индекс в пути
DSN, задаётся в `.env.test`); сценарии Behat очищают её перед каждым
сценарием. Без этого сценарии увеличивали бы `items:seq` и писали `item:*`
в данные dev-окружения. Окружение `e2e` (сессии в Redis) этим не
затрагивается.

Очистка — `#[BeforeScenario]` контекста `ItemStorageContext`: в тестовую БД
пишет только домен `item_storage`, так что это приватное состояние домена,
и шаг «хранилище записей не изменилось» опирается на очистку своего же
контекста. Защита от `FLUSHDB` БД 0 — там же. `HttpApiContext` от Redis
не зависит. `#[BeforeScenario]`, а не `@AfterScenario` по общему правилу
уборки: прогон не зависит от остатков упавшего. Это исключение записывается
в ADR 0002 и в `features/CLAUDE.md`.

### D8. ADR

Нужен `docs/adr/0004-…`: хранение записей в Redis вместо in-memory из текста
задания. Решение дорого отменить (меняет модель данных и поведение при
нескольких экземплярах) и за ним стоит трейдоф (зависимость от внешнего
сервиса против share-nothing PHP-FPM).

### D9. Формулировки «шаблон»

- Delta `specs/qa`: `RENAMED` для требования о QA-инструментах и `MODIFIED`
  с полными блоками для четырёх требований — меняется только субъект
  («Проект MUST»).
- `Purpose` спек `platform` и `qa` правится в `openspec/specs/*/spec.md`
  напрямую: delta его не переносит.
- `context` в `openspec/config.yaml` правится первой задачей: он подставляется
  во все последующие инструкции OpenSpec.
- В `features/admin_access_anonymous_entry.feature` правится только
  повествование `Feature` («границу аутентификации шаблона»); шаги
  и сценарий не меняются, поэтому строка «уже покрыта» для qa «Главная
  открывается в браузере» остаётся в силе.

## Стратегия тестирования

### Behat

Все строки — без `@javascript`, сессия `symfony` (ядро в процессе Behat,
без браузера и веб-сервера). Наборы данных — строки `Examples` у
`Scenario Outline`, колонка `набор`.

| Сценарий спеки | Домен | Теги | Feature-файл | Контекст | Тип теста | Состояние | Обоснование |
|---|---|---|---|---|---|---|---|
| items: Успешное создание | `item_storage` | `@domain:item_storage` | `features/item_storage_create.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | сценарий «запись создаётся»: код 201, тело `{"id": N}`, `N > 0` |
| items: Два создания дают разные id | `item_storage` | `@domain:item_storage` | `features/item_storage_create.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | сценарий «два создания дают разные id» |
| items: Созданная запись видна через другой экземпляр | `item_storage` | `@domain:item_storage` | `features/item_storage_create.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | сценарий «запись видна через другой экземпляр»; второй экземпляр и проверка, что запись лежит вне процесса, — в «Решениях» |
| items: Чтение созданной записи | `item_storage` | `@domain:item_storage` | `features/item_storage_read.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «значение читается таким, каким записано», набор `string-foo` |
| items: Тип значения сохраняется | `item_storage` | `@domain:item_storage` | `features/item_storage_read.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «значение читается таким, каким записано», наборы `string-digit`, `int`, `float`, `true`, `false`, `empty-string` |
| items: Несуществующий id | `item_storage` | `@domain:item_storage` | `features/item_storage_read.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «запись не найдена», наборы `missing-id`, `huge-id` (id длиннее 18 цифр, D1), утверждение «код 404» |
| items: id не является числом | `item_storage` | `@domain:item_storage` | `features/item_storage_read.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «запись не найдена», набор `non-numeric-id` |
| items: Ошибка отдаётся в JSON | `item_storage` | `@domain:item_storage` | `features/item_storage_read.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «запись не найдена», утверждение «`Content-Type: application/json`, тело — JSON-объект» при `Accept: */*`; набор `accept-html` (`Accept: text/html`) — независимость от `Accept`; шаг отправки с заданным `Accept` — в `ItemStorageContext` |
| items: Замена значения | `item_storage` | `@domain:item_storage` | `features/item_storage_replace.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | сценарий «значение заменяется» |
| items: Замена несуществующей записи | `item_storage` | `@domain:item_storage` | `features/item_storage_replace.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | сценарий «несуществующая запись не создаётся заменой» |
| items: Замена по id, не являющемуся числом | `item_storage` | `@domain:item_storage` | `features/item_storage_replace.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | сценарий «замена по нечисловому id»: `PUT /items/abc` с телом `{}` → 404 от роутера раньше проверки тела (D1), `Content-Type: application/json`, тело — JSON-объект |
| items: Невалидное тело не меняет запись | `item_storage` | `@domain:item_storage` | `features/item_storage_replace.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | сценарий «невалидное тело не меняет запись» |
| items: Невалидное тело при несуществующей записи | `item_storage` | `@domain:item_storage` | `features/item_storage_replace.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | сценарий «невалидное тело отклоняется раньше поиска записи» |
| items: Нет ключа value | `item_storage` | `@domain:item_storage` | `features/item_storage_invalid_body.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «невалидное тело отклоняется», набор `missing-value` |
| items: Лишний ключ | `item_storage` | `@domain:item_storage` | `features/item_storage_invalid_body.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «невалидное тело отклоняется», набор `extra-key` |
| items: Значение null | `item_storage` | `@domain:item_storage` | `features/item_storage_invalid_body.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «невалидное тело отклоняется», набор `null-value` |
| items: Значение — массив или объект | `item_storage` | `@domain:item_storage` | `features/item_storage_invalid_body.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «невалидное тело отклоняется», наборы `array-value`, `object-value` |
| items: Невалидный JSON | `item_storage` | `@domain:item_storage` | `features/item_storage_invalid_body.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «невалидное тело отклоняется», набор `invalid-json` |
| items: Тело не в формате JSON | `item_storage` | `@domain:item_storage` | `features/item_storage_invalid_body.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «невалидное тело отклоняется», наборы `form-body` и `x-json-body` (`Content-Type: application/x-json`, D5); шаг отправки с произвольным `Content-Type` — в `ItemStorageContext` |
| items: Число вне диапазона | `item_storage` | `@domain:item_storage` | `features/item_storage_invalid_body.feature` | `ItemStorageContext`, `HttpApiContext` | новое поведение | новая | outline «невалидное тело отклоняется», набор `overflow-float` |
| health: Хранилище доступно | `service_health` | `@domain:service_health` | `features/service_health_readiness.feature` | `ServiceHealthContext`, `HttpApiContext` | новое поведение | новая | сценарий «сервис готов»: код 200, `Content-Type: application/json`, тело — JSON-объект |
| health: Хранилище недоступно | `service_health` | `@domain:service_health` | `features/service_health_readiness.feature` | `ServiceHealthContext`, `HttpApiContext` | новое поведение | новая | outline «хранилище недоступно», наборы `ping-fails` и `connect-fails`: код 503, `Content-Type: application/json`, тело — JSON-объект; швы — в «Решениях» |

### PHPUnit

| Сценарий спеки | Точка наблюдения | Suite | Тип теста | Что нужно снаружи | Базовый класс | Состояние | Обоснование |
|---|---|---|---|---|---|---|---|
| qa: E2E не подменяет сессии на mock_file | ключ `sf_s:` в Redis после сохранения сессии | Integration | characterization | ядро, Redis | `KernelTestCase` | уже покрыта | `tests/Integration/Session/E2eSessionStorageTest.php` |
| qa: Главная открывается в браузере | адрес страницы в браузере | E2E | characterization | браузер | `PantherTestCase` | уже покрыта | `tests/E2E/HomepageTest.php`; Behat — `features/admin_access_anonymous_entry.feature` |

Итого строк: 24 — Behat: новая 22; PHPUnit: уже покрыта 2. Переписывается 0,
`БЛОКИРОВАНО` 0, `ТРЕБУЕТ РЕШЕНИЯ` 0.

**Сверка со спекой.** Сценариев в дельтах 29: `items` — 21, `health` — 2,
`qa` — 6. В таблицах — 24, прозой ниже — 5, не названных нигде нет.
Нормативных фраз без своего сценария найдено 3, все названы ниже.

**Покрыто не своей строкой:**

- items «Запрос без учётных данных» — строка «Успешное создание»: сценарий
  «запись создаётся» шлёт `POST` без заголовков аутентификации и ждёт 201.

**Не покрывается вовсе:**

- qa «Запуск слоёв через Makefile», «Локальный запуск E2E через Makefile»,
  «CI разделяет Unit/Behat и E2E», «Обычный test-контур остаётся без
  обязательного Redis для сессий» — тестов на них нет и до change; это
  свойства `Makefile`, `.gitlab-ci.yml` и `when@test` в конфигурации,
  наблюдаемые самим прогоном `make qa` и pipeline. Delta меняет только
  формулировки.

**Нормативные фразы без сценария:**

- items, «Тело запроса записи»: «MUST NOT изменять хранилище» для `POST` —
  второе утверждение outline «невалидное тело отклоняется» на всех его
  наборах: после запроса тестовая БД Redis пуста (`DBSIZE` = 0; она
  очищается перед сценарием, D7), так что проверка не повторяет схему
  ключей `ItemRepository`.
- items, «Формат ответов и доступ»: JSON и `Content-Type` для **успешных**
  ответов — шаг про `Content-Type` в сценариях «запись создаётся»,
  «значение заменяется» и в outline «значение читается таким, каким
  записано»; для ответов **400** и 404 после `PUT` — шаги про `Content-Type`
  и «тело ответа — JSON-объект» в outline «невалидное тело отклоняется»
  и в сценариях `features/item_storage_replace.feature` с кодами ошибок;
  запросы шлют `Accept: */*`.
- items и health: доступ без аутентификации для `GET`, `PUT`, `/health` —
  структурно: ни один сценарий не шлёт учётных данных.

**PHPUnit** для нового поведения сознательно не задействован: актор ручек —
нагрузочный скрипт, внешний потребитель (ответ на Вопрос 1). В таблице PHPUnit
только существующие проверки сценариев `qa`.

### Решения

- Вопрос 1: ответ пользователя — «актор нужен, это — нагрузочный скрипт»;
  для `/health` — «Внешний — Behat» (читает нагрузочный скрипт; healthcheck
  compose на `/health` пока не заведён). Прежний ответ «PHPUnit
  (WebTestCase)» и ответ на Вопрос 2 этим отменены.
- Вопрос 4 не задан: живым остался вариант «без браузера» — ручки отдают
  HTTP + JSON, страницы и JS у актора нет.
- Вопрос 5: ответы пользователя — «item_storage» для `/items`,
  «service_health» для `/health`. Строки реестра доменов
  в `features/CLAUDE.md` добавляются вместе с первыми сценариями этих
  доменов, не раньше.
- Потолка наблюдения нет: драйвер BrowserKit отдаёт код ответа, заголовки
  и тело (`Session::getStatusCode()`, `getResponseHeader()`,
  `getPage()->getContent()`). Ограничение по сравнению с Panther, где кода
  ответа нет, к этим строкам не относится.
- Mink рассчитан на браузерные действия, а запросов с методом и JSON-телом
  в его API нет. Запросы шлёт трейт `SendsJsonRequests` через клиент
  драйвера (`BrowserKitDriver::getClient()->request(…)`); `Content-Type` —
  `application/json` по умолчанию, `Accept` — `*/*` по умолчанию, оба можно
  задать шагом `ItemStorageContext`.
  Ответ читается через сессию Mink.
- Ни nginx, ни PHP-FPM сценариям не нужны: сессия `symfony` выполняет
  запрос ядром в процессе Behat. В GitLab job `qa:behat-env` уже поднимает
  Redis сервисом (`.gitlab-ci.yml`).
- «Другой экземпляр сервиса» — следующее ядро: `KernelBrowser` перезагружает
  ядро перед каждым запросом сценария, кроме первого, а Redis остаётся
  тем же. Одновременные запросы не воспроизводятся: уникальность при гонке
  держит атомарность `INCR` (D4), детерминированно её не проверить.
- Новое ядро живёт в том же процессе PHP, что и прежнее: хранилище в памяти
  процесса (`static`-массив) прошло бы чтение через другой экземпляр. Поэтому
  шаг чтения через другой экземпляр до запроса проверяет, что тестовая БД
  Redis не пуста (`DBSIZE` > 0): запись лежит вне процесса Behat. Схему ключей
  `ItemRepository` проверка не повторяет.
- Шов для 503: `ServiceHealthContext` получает контейнер ядра драйвера
  (`behat.driver.service_container`, автосвязывание по
  `ContainerInterface $driverContainer` —
  `vendor/friends-of-behat/symfony-extension/src/Bundle/DependencyInjection/FriendsOfBehatSymfonyExtensionExtension.php`)
  и до первого запроса сценария подменяет приватный сервис `Redis`
  двойником, бросающим `\RedisException` на `ping()`. `TestContainer::set()`
  умеет подменять приватный сервис, пока он не создан. Подмена действует
  только на первый запрос сценария: перед вторым ядро перезагрузится. Поэтому
  сценарий «хранилище недоступно» делает ровно один запрос. Наблюдательный
  прогон до появления контроллера невозможен. Это шов набора `ping-fails`.
- Шов набора `connect-fails`: ветка D6, где падает сам `createConnection`
  (исключение Cache, а не `\RedisException` от `PING`). `ServiceHealthContext`
  до первого запроса подменяет `REDIS_URL` на недоступный адрес: `%env()%`
  разрешается при создании сервиса `Redis`, то есть уже в запросе. Подмена
  меняет `$_ENV` и `$_SERVER` — глобальное состояние процесса Behat, а не
  приватное состояние контекста. Её делает и отменяет только
  `ServiceHealthContext`: прежнее значение возвращает его `#[AfterScenario]`,
  и других читателей `REDIS_URL` между шагом и уборкой нет, кроме ядра
  драйвера. Это сказано в docblock'е шага.
  Проверено прогоном: без ветки исключения Cache в `HealthController` набор
  даёт 500 (`Redis connection failed: Connection refused`), с ней — 503;
  запасной путь из Risks не понадобился.
- `form-body` в сессии `symfony`: BrowserKit кладёт в `$request->request`
  только параметры запроса и не разбирает тело формы, как это делает PHP-FPM.
  `SendsJsonRequests` для `application/x-www-form-urlencoded` передаёт
  разобранное тело параметрами; без этого набор проходил бы и без проверки
  `Content-Type` в резолвере.
- Сверка с `vendor/` (FOSRest 3.9.0), D1 и D5 подтверждены, запасные варианты
  не нужны. `ViewResponseListener::onKernelController` для объекта
  с `__invoke` читает атрибуты метода `__invoke` (`IS_INSTANCEOF`
  `Rest\View`); `onKernelView` ставит `statusCode` атрибута, когда у `View`
  кода нет или он 200 — при `force` объект оборачивается в `View` без кода.
  `BodyListener` для `POST`/`PUT` декодирует непустое тело
  (`JsonDecoder` — `@json_decode($data, true)`) и бросает
  `BadRequestHttpException`, если результат не массив: невалидный JSON,
  `null`, скаляр. JSON-массив (`[1]`) проходит как массив с ключом `0`,
  его отклоняет резолвер: ключи не `['value']`.
- Скиллу `behat-tests`: имена наборов в `Examples` — те же, что в таблице
  (`missing-value`, `string-digit`, …), чтобы падение называло набор.

### Предусловия

Все три — общие для всего раздела и идут первыми задачами тестов, в этом
порядке. Production-код ни одно не затрагивает.

1. **Сессия Behat без браузера.** Dev-зависимость `behat/mink-browserkit-driver`
   (`composer.json`, `composer.lock`); сессия `symfony` в `behat.yml.dist`
   как сессия по умолчанию, `panther` остаётся `javascript_session`;
   таблица «Сессии» в `features/CLAUDE.md` и комментарий в `behat.yml.dist`
   больше не говорят «пока не настроена».
2. **Изоляция в Redis.** Test-окружение пишет в отдельную БД Redis:
   `REDIS_URL` с индексом БД в `.env.test` (D7); очистка этой БД
   (`FLUSHDB`) в `#[BeforeScenario]` контекста `ItemStorageContext` —
   исключение из правила уборки (D7).
3. **Общий контекст HTTP-шагов.** `features/bootstrap/HttpApiContext.php`
   держит только шаги, которые используют оба домена (правило «Где живёт шаг»
   в `features/CLAUDE.md`): запрос без тела, проверки кода ответа,
   `Content-Type` и «тело ответа — JSON-объект». Запрос с JSON-телом,
   с заданными `Content-Type` или `Accept` и сравнение тела с JSON нужны
   только `item_storage` и живут в `ItemStorageContext`. Запрос без тела
   и запрос с телом — варианты одного действия, а не симметричная пара
   «есть»/«нет» из ADR 0002, поэтому разносятся по правилу общности. Помощники запроса и чтения ответа — трейт
   `SendsJsonRequests`. Контекст регистрируется в `behat.yml.dist`.

## Наблюдаемость

Источник — состояние Redis: он есть и в prod, и сервисом в GitLab-пайплайне.
Коды ответов ручек здесь не наблюдаются: в prod их видит только актор
(нагрузочный скрипт, часть 3 HW1), а в пайплайне их проверяют сценарии Behat
из «Стратегии тестирования». Журналы nginx и PHP-FPM не используются:
в пайплайне их нет.

Запросы — после smoke-вызовов `POST /items` с телом `{"value":"x"}` и
`PUT /items/<id>` с телом `{"value":"y"}`. `redis-cli` —
`docker compose exec redis redis-cli --no-auth-warning`, пароль берётся из
`REDISCLI_AUTH` сервиса.

**Цель change достигнута:**

| Сигнал | Источник | Запрос | Порог |
|---|---|---|---|
| созданная запись лежит в Redis | Redis | `redis-cli GET item:<id из ответа>` | pass: `{"value":"x"}` |
| счётчик id растёт | Redis | `redis-cli GET items:seq` до и после smoke-`POST` | pass: после = до + 1 |
| замена записана | Redis | `redis-cli GET item:<id>` после smoke-`PUT` | pass: `{"value":"y"}` |

**Здоровье, которое change сдвигать не должен:**

| Сигнал | Источник | Запрос | Порог |
|---|---|---|---|
| сессии по-прежнему пишутся в Redis | Redis | `redis-cli --scan --pattern 'sf_s:*' \| head -1` после входа в `/admin/login` | pass: ключ найден |
| change пишет только свои ключи | Redis | `redis-cli --scan \| grep -cvE '^(items:seq\|item:[0-9]+\|sf_s:.*)$'` до и после smoke-вызовов | pass: значения равны |

Отсутствующих сигналов нет.

Раздела «Чувствительность проверок» нет: change не добавляет
характеризационных проверок. Обе строки characterization — уже существующие
тесты в состоянии «уже покрыта»; они должны остаться зелёными и не удаляются.

## Risks / Trade-offs

- [Redis недоступен → `/items` отвечают 500] → принято: specs этого не
  описывают; `/health` в этот момент отвечает 503, по нему зависимые сервисы
  могут не слать трафик (healthcheck compose на `/health` пока не заведён).
- [`body_listener` пропускает тела форм и тела без `Content-Type`] →
  резолвер (D5) отвечает 400 на любой не-JSON `Content-Type`, в том числе
  на отсутствующий. Клиенты (нагрузочный скрипт) обязаны слать заголовок;
  в README это указывается вместе с примерами запросов.
- [Подмена `REDIS_URL` в наборе `connect-fails` может не дойти до контейнера
  ядра драйвера: значение env кешируется контейнером] → проверено прогоном
  (Решения): подмена доходит, ветка `createConnection` срабатывает, запасной
  путь не понадобился.
- [`#[Rest\View(statusCode: 201)]` на `__invoke` без базового класса FOSRest]
  → чтение атрибута проверяется по коду `ViewResponseListener` из `vendor/`
  (скилл `vendor-class-lookup`) до написания контроллера; запасной вариант —
  вернуть `View::create($model, 201)`.
- [`float` с нулевой дробной частью (`1.0`) может сериализоваться как `1`
  при другом `serialize_precision`] → specs проверяют `1.5`; граничный случай
  не обещан.
- [Целые за пределами `PHP_INT_MAX` декодируются во `float`] → вне объёма
  домашней работы.
- [Тесты и dev делят один инстанс Redis] → отдельная БД (D7); `FLUSHDB`
  затрагивает только её.
- [Контейнер ядра драйвера, полученный контекстом, может оказаться от ядра
  до перезагрузки между сценариями, и подмена `Redis` не дойдёт до запроса]
  → первым прогоном сценария «хранилище недоступно» проверить, что ответ 503;
  если нет — брать контейнер в шаге через ядро драйвера
  (`fob_symfony.driver_kernel`), а не в конструкторе контекста. Проверено
  задачей 4.12: ответ 503.
- [Новая dev-зависимость `behat/mink-browserkit-driver`] → только
  `require-dev`, в prod-образ не попадает.

## Migration Plan

Миграции данных нет: ключи `items:seq` и `item:*` появляются с первым
`POST`. Откат — revert change; оставшиеся ключи ни на что не влияют.
