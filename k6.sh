#!/bin/bash
# Прогоняет docker/k6/scenario.js против запущенного стека: тот же образ, сеть
# и BASE_URL, что у сервиса grafana-k6. Аргументы уходят в `k6 run`:
#   ./k6.sh --vus 5 --duration 30s
set -euo pipefail

cd "$(dirname "$0")"

exec docker compose run --rm --no-deps grafana-k6 run "$@" /k6/scenario.js
