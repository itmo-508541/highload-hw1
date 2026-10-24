# syntax=docker/dockerfile:1.19
ARG source_image=source
FROM debian:trixie-slim AS php

ENV TERM=linux
ENV DEBIAN_FRONTEND=noninteractive

# PHP 8.4.27 (21.10.2026) закрывает CVE-2026-109618 и CVE-2026-109619 (PHP2Shell,
# RCE без аутентификации): ниже этой версии образ собирать нельзя.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
            apt-transport-https gnupg ca-certificates curl \
            netcat-traditional \
    && install -m 0755 -d /usr/share/keyrings \
    && curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ trixie main" | tee /etc/apt/sources.list.d/sury-php.list \
    && apt-get update \
    && apt-get satisfy -y --no-install-recommends \
            "php8.4-curl" \
            "php8.4-fpm (>= 8.4.27)" \
            "php8.4-gd" \
            "php8.4-intl" \
            "php8.4-mbstring" \
            "php8.4-mysql" \
            "php8.4-redis" \
            "php8.4-protobuf" \
            "php8.4-xml" \
            "php8.4-zip" \
    && apt-get purge --autoremove -y gnupg \
    && apt-get clean -y \
    && curl -sS -L -o /usr/local/bin/phing https://github.com/phingofficial/phing/releases/download/3.1.2/phing-3.1.2.phar \
    && chmod +x /usr/local/bin/phing \
    && rm -rf /var/lib/apt/lists/* /usr/share/man/* /usr/share/doc/* /var/cache/* /var/log/* /tmp/* \
    && chown www-data:www-data /srv

WORKDIR /srv
COPY docker/php /etc/php/8.4/
EXPOSE 9000
USER www-data
CMD ["php-fpm8.4"]


# Используется для локальной разработки
FROM php AS dev

USER root
COPY docker/php-dev /etc/php/8.4/
COPY --from=composer/composer:latest-bin /composer /usr/local/bin/composer

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
        php8.4-xdebug \
        chromium \
        chromium-driver
USER www-data

ENV PANTHER_NO_SANDBOX=1
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'

# Используется для подготовки кода и ассетов для prod образа
FROM dev AS source

ENV APP_ENV="prod"
COPY --chown=www-data:www-data *.json *.lock ./

RUN composer check-platform-reqs \
    && composer install \
        --no-dev \
        --prefer-dist \
        --no-scripts \
        --optimize-autoloader \
        --no-interaction

COPY --chown=www-data:www-data . .

RUN phing app-deploy -Dapp.env=prod


# Используется для копирования файлов для prod образа
FROM ${source_image} AS source_image


# Используется для конечного приложения
FROM php AS prod

ENV APP_ENV="prod"

COPY \
    --exclude=.claude \
    --exclude=.env \
    --exclude=.env.* \
    --exclude=config/jwt \
    --exclude=patterns \
    --exclude=docs \
    --exclude=features \
    --exclude=node_modules \
    --exclude=openspec \
    --exclude=package.json \
    --exclude=package-lock.json \
    --exclude=secrets \
    --exclude=tests \
    --from=source_image \
    --chown=www-data:www-data \
    /srv/ .
