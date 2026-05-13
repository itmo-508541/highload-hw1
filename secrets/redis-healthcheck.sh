#!/bin/sh
# Healthcheck для Redis с паролем из смонтированного secret-файла конфигурации.
# Читает первую незакомментированную строку requirepass и вызывает redis-cli через REDISCLI_AUTH.

set -eu

CONF="${REDIS_CONF_PATH:-/run/secrets/redis.conf}"

if ! [ -r "$CONF" ]; then
	printf '%s\n' "redis-healthcheck: cannot read ${CONF}" >&2
	exit 1
fi

# Пропускаем пустые и строки-комментарии; первая активная requirepass.
PASSWORD=$(awk '
	/^[[:space:]]*#/ { next }
	/^[[:space:]]*$/ { next }
	/^[[:space:]]*requirepass[[:space:]]/ {
		sub(/^[[:space:]]*requirepass[[:space:]]+/, "")
		gsub(/[[:space:]]+$/, "", $0)
		print
		exit
	}
' "$CONF")

if [ -z "$PASSWORD" ]; then
	printf '%s\n' "redis-healthcheck: no requirepass in ${CONF}" >&2
	exit 1
fi

export REDISCLI_AUTH="$PASSWORD"
RESPONSE=$(redis-cli --no-auth-warning PING 2>/dev/null | tr -d '\r') || exit 1
[ "$RESPONSE" = 'PONG' ] || exit 1
