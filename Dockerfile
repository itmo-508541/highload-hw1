ARG source_image=scratch
FROM debian:trixie-slim AS php

ENV TERM=linux
ENV DEBIAN_FRONTEND=noninteractive
ENV HOME=/srv

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
            apt-transport-https gnupg ca-certificates curl \
            netcat-traditional \
    && install -m 0755 -d /usr/share/keyrings \
    && curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ trixie main" | tee /etc/apt/sources.list.d/sury-php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends --allow-unauthenticated \
            php8.4-curl \
            php8.4-fpm \
            php8.4-gd \
            php8.4-intl \
            php8.4-mbstring \
            php8.4-mysql \
            php8.4-xml \
            php8.4-zip \
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


# Используется для копирования файлов для prod образа
FROM ${source_image} AS source_image


# Используется для локальной разработки
FROM php AS dev

USER root
COPY docker/php-dev /etc/php/8.4/
COPY --from=composer/composer:latest-bin /composer /usr/local/bin/composer

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends php8.4-xdebug
USER www-data

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

RUN phing app-deploy -Dsymfony.env=prod


# Используется для конечного приложения
FROM php AS prod

ENV APP_ENV="prod"

COPY --from=source_image --chown=www-data:www-data /srv/ .
