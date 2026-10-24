# Redis (операционный runbook)

Сессии Symfony, записи `/items` и smoke-проверки Redis в Docker. Переменные `REDIS_URL` /
`ENV_redis_*` — в [`dotenv.md`](dotenv.md). Быстрый старт проекта — в
[`README.md`](../../README.md).

## Сессии приложения

Сессии хранятся в **Redis** через
`Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler`
(см. `config/packages/framework.yaml`). Сервис `\Redis` собирается фабрикой
`Symfony\Component\Cache\Adapter\RedisAdapter::createConnection(%env(REDIS_URL)%)`
(см. `config/services.yaml`). Префикс ключей в Redis — `sf_s:`.

В тестовом окружении (`when@test:` в `config/packages/framework.yaml`)
`storage_factory_id` подменяется на `session.storage.factory.mock_file`, поэтому
`make test` (PHPUnit Unit) **не требует** работающего Redis ради сессий;
`make behat` Redis нужен ради записей `/items` (раздел ниже). Контур E2E (`APP_ENV=e2e`, `make test-env` /
`make behat`, CI `qa:phpunit-env` / `qa:behat-env`) пишет сессии в Redis
как dev/prod — см. [`e2e.md`](e2e.md).

## Записи `/items`

Записи хранятся в том же Redis (ADR [0004](../adr/0004-items-stored-in-redis.md)),
репозиторий — `App\Repository\ItemRepository`:

| Ключ | Что хранит |
|---|---|
| `items:seq` | счётчик id, `INCR` на каждое создание |
| `item:<id>` | JSON-строка `{"value": X}`: JSON сохраняет тип значения |

Тестовое окружение пишет в отдельную БД Redis: индекс `/1` в `REDIS_URL`
из `.env.test`. Перед каждым сценарием Behat `HttpApiContext` очищает её
(`FLUSHDB`) и отказывается чистить БД `0` — данные dev-окружения. Поэтому
`make behat` и сценарии без `@javascript` требуют работающего Redis.

```bash
# счётчик и одна запись
docker compose exec redis redis-cli GET items:seq
docker compose exec redis redis-cli GET item:<id>

# ключи тестовой БД
docker compose exec redis redis-cli -n 1 --scan
```

Persistence между перезапусками — именованный том `redis-data` (в
`docker-compose.yml` и `docker-compose.deploy.yml`).

## Smoke из dev-Compose

Контейнеры должны быть подняты (`make up`).

```bash
# список ключей сессий
docker compose exec redis redis-cli -a "$ENV_redis_password" --no-auth-warning KEYS 'sf_s:*'

# TTL конкретной сессии
docker compose exec redis redis-cli -a "$ENV_redis_password" --no-auth-warning TTL 'sf_s:<id>'
```

В **`docker-compose.yml`** (локально) у сервиса **`redis`** задано
**`REDISCLI_AUTH: "root"`** (как в **`.env`**), поэтому
**`docker compose exec redis redis-cli PING`** внутри контейнера отвечает
**`PONG`** без `-a`. На **проде** в **`docker-compose.deploy.yml`** у **`redis`**
переменных **`ENV_*` в `environment:` нет** — пароль только в
**`/run/secrets/redis.conf`**, healthcheck читает его из файла (см.
**`secrets/redis-healthcheck.sh`**).

`PING` без проверки пароля не доказывает, что AUTH включён. Чтобы убедиться, что
сервер отклоняет неверный пароль:

```bash
docker compose exec redis redis-cli -a wrong_password --no-auth-warning PING
# → (error) WRONGPASS invalid username-password pair...
```

## Связанные runbook'и

- Env и `REDIS_URL` — [`dotenv.md`](dotenv.md)
- Phing / `make init` — [`phing.md`](phing.md)
