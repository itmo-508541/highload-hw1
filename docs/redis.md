# Redis (операционный runbook)

Сессии Symfony и smoke-проверки Redis в Docker. Переменные `REDIS_URL` /
`ENV_redis_*` — в [`dotenv.md`](dotenv.md). Быстрый старт проекта — в
[`README.md`](../README.md).

## Сессии приложения

Сессии хранятся в **Redis** через
`Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler`
(см. `config/packages/framework.yaml`). Сервис `\Redis` собирается фабрикой
`Symfony\Component\Cache\Adapter\RedisAdapter::createConnection(%env(REDIS_URL)%)`
(см. `config/services.yaml`). Префикс ключей в Redis — `sf_s:`.

В тестовом окружении (`when@test:` в `config/packages/framework.yaml`)
`storage_factory_id` подменяется на `session.storage.factory.mock_file`, поэтому
`make test` (PHPUnit) и `make behat` локально **не требуют** работающего Redis.
В CI (`qa:behat`, см. [`.gitlab-ci.yml`](../.gitlab-ci.yml)) Redis всё равно
поднимается как сервис — ради согласованности с dev-стендом и совместимости с
возможными будущими сценариями, которым понадобится реальное подключение.

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
