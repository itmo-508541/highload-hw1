# Инструменты автоматизации в Grafana

[Домашняя работа](HW1.md): микросервис на Symfony 7.4 / PHP 8.4. CRUD-сервис на PHP 8.4 отдаёт метрики золотых сигналов через OpenTelemetry в Prometheus и далее в Grafana.

## Запуск

```bash
cp .env.example .env
docker compose up -d --build
```

Через 1-2 минуты нагрузка выходит на полку, и на дашборде в Grafana появляются данные: <http://localhost:3000/d/highload-hw1>. Логин `admin`, пароль - `ENV_grafana_admin_password` из `.env`.

## Приложение

Нет возможности запустить Web-сервер на "голом" PHP, поэтому используется Nginx. Также не существует In-memory хранилища, поэтому используется Redis. Из-за невозможности организации "In-memory" хранилища нельзя отправлять данные для метрик сразу же в Prometheus и, как промежуточное звено, был установлен контейнер OpenTelemetry Collector.

| Ручка | Ответ |
|---|---|
| `POST /items` | `201 {"id": N}` |
| `GET /items/{id}` | `200 {"value": X}`; нет записи - `404` |
| `PUT /items/{id}` | `200 {"id": N}`; нет записи - `404` |
| `GET /health` | `200`, когда Redis доступен; иначе `503` |
| `GET /items/dashboard` | JSON дашборда Grafana (см. «Дашборд») |

Метрики в Prometheus отдаются из OpenTelemetry Collector `http://otelcollector:8889/metrics`

Два экземпляра приложения собраны из одного образа и отличаются только переменными окружения `POD_ID` и `DATACENTER`.

## Состав стека и порядок запуска

| Сервис | Образ | Роль |
|---|---|---|
| `init` | сборка `Dockerfile`, `target: prod` | одноразовый: дамп env, миграции, ассеты (`phing`) |
| `php`, `spare` | тот же образ | два экземпляра приложения (PHP-FPM) |
| `nginx` | сборка `Dockerfile-nginx` | вход на `:80`, healthcheck через `/health` |
| `mysql` | `mysql:8.0-debian` | БД |
| `redis` | `redis:8-trixie` | хранилище записей |
| `otelcollector` | `otel/opentelemetry-collector-contrib:0.163.2` | приём OTLP, отдача `/metrics` |
| `prometheus` | `prom/prometheus:v3.15.1` | скрейпит коллектор раз в 15 с |
| `grafana` | `grafana/grafana:12.2.0` | datasource и дашборд через provisioning |
| `grafana-init` | `alpine:3.24.3` | одноразовый: скачивает JSON дашборда |
| `grafana-k6` | `grafana/k6:1.8.2` | фоновая нагрузка |

## Метрики

Ручка подключается к метрикам атрибутом `#[CollectMetrics(name: '...')]` на контроллере. Имя из атрибута становится значением лейбла `controller`.

`App\EventListener\CollectMetricsListener` подписан на события ядра Symfony:

- `kernel.request` (приоритет 100, раньше роутера и firewall) запоминает время начала;
- `kernel.controller` читает атрибут `#[CollectMetrics]`;
- `kernel.terminate` записывает latency и счётчик с кодом ответа.

`/health` и `/items/dashboard` не размечены атрибутом и не получают лейбл `controller`.

| Метрика в Prometheus | Тип | Единица | Сигнал |
|---|---|---|---|
| `web_latency_milliseconds` (`_bucket`, `_sum`, `_count`) | histogram | мс | latency |
| `web_throughput_total` | counter | запросы | RPS и ошибки |

| Лейбл | Значения | Откуда |
|---|---|---|
| `controller` | `api_items_create`, `api_items_read`, `api_items_update` | `#[CollectMetrics(name:)]` |
| `status_code` | `200`, `201`, `400`, `403`, `404`, `406`, … | код ответа |
| `pod_id` | `pod-1`, `pod-2` | env `POD_ID` |
| `datacenter` | `dc-eu-west-2`, `dc-us-east-5` | env `DATACENTER` |

Для корректной агрегации по нескольким экземплярам приложения лучше всего использовать Histogram, потому что оно разбивает все наблюдения (например, время выполнения запроса) на заранее заданные интервалы (buckets) и подсчитывает, сколько значений попало в каждый из них. Это позволяет увидеть распределение задержек:

-  `0, 5, 10, 25, 50, 75, 100, 250, 500, 1000` мс, плюс `+Inf`. Это границы OpenTelemetry SDK по умолчанию.

## Дашборд

Структура дашборда создаётся самим приложением: оно само знает как показывать его метрики. При этом задачей контейнера `grafana-init` - всего лишь скачать JSON из `/items/dashboard` и положить в нужную директорию.

1. При сборке DI-контейнера Symfony, `CollectMetricsPass` находит все контроллеры с `#[CollectMetrics]` и сохраняет имена в параметр `app.collect_metrics.names`.
2. `GET /items/dashboard` строит дашборд классом `App\Grafana\DashboardGenerator` на `grafana/foundation-sdk` и отдаёт JSON.
3. `grafana-init` скачивает этот JSON в том `grafana-data`. Grafana подхватывает его через file provisioning (`docker/grafana/dashboards/default.yml`).

Список ручек в генераторе не хранится. Новая ручка с атрибутом `#[CollectMetrics]` появится на дашборде после перезапуска стека.

Дашборд `highload-hw1` в папке `S1`, только для чтения.

**Переменные:**

| Переменная | Тип | Значения |
|---|---|---|
| `controller` | custom, одно значение | `All`, `None` и имена ручек |
| `datacenter` | query, multi + All | `label_values(web_throughput_total{controller=~"$controller"}, datacenter)` |
| `pod_id` | query, multi + All | `label_values(web_throughput_total{controller=~"$controller",datacenter=~"$datacenter"}, pod_id)` |

**Панели.** Общий селектор:
`job="otelcollector",controller=~"$controller",datacenter=~"$datacenter",pod_id=~"$pod_id"`.

| Панель | PromQL |
|---|---|
| Latency (ok), p50/p95/p99 | `histogram_quantile(φ, sum by (le) (rate(web_latency_milliseconds_bucket{…,status_code=~"[123].."}[$__rate_interval])))` |
| Latency (error), p50/p95/p99 | то же с `status_code=~"[45].."` |
| RPS | `sum(rate(web_throughput_total{…}[$__rate_interval]))` |
| Ошибки по кодам ответа | `sum by (status_code) (rate(web_throughput_total{…,status_code=~"[45].."}[$__rate_interval]))` |

## Нагрузка

k6 запускается сервисом `grafana-k6` после того, как `nginx` стал healthy и
`grafana-init` успешно завершился. Сценарий - `docker/k6/scenario.js`, запросы
идут на `http://nginx`, то есть на оба экземпляра.

| Сценарий | Executor | Ручка |
|---|---|---|
| `api_items_create` | `constant-vus` | `POST /items` |
| `api_items_read` | `ramping-arrival-rate` | `GET /items/{id}` |
| `api_items_update` | `ramping-arrival-rate` | `PUT /items/{id}` |

Итого около 100 RPS на оба экземпляра.

## Секреты

Пароль администратора Grafana и пароли БД и Redis задаются в `.env`, который
не хранится в репозитории (`.gitignore`). Пример - `.env.example`. Без `.env`
compose завершается с ошибкой (`${ENV_grafana_admin_password:?}`).

Если в переменной с паролем от Grafana завести значение `admin`, то при первом запуске Grafana попросит сменить пароль.
