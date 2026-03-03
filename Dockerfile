ARG source_image=scratch
FROM debian:trixie-slim AS php

ENV TERM linux
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
            apt-transport-https gnupg ca-certificates curl \
            netcat-traditional \
    && install -m 0755 -d /usr/share/keyrings \
    && curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ trixie main" | tee /etc/apt/sources.list.d/sury-php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends --allow-unauthenticated \
            php8.2-curl \
            php8.2-fpm \
            php8.2-gd \
            php8.2-intl \
            php8.2-mbstring \
            php8.2-mysql \
            php8.2-xml \
            php8.2-zip \
    && apt-get purge --autoremove -y gnupg \
    && apt-get clean -y \
    && curl -sS -L -o /usr/local/bin/phing https://github.com/phingofficial/phing/releases/download/3.0.0/phing-3.0.0.phar \
    && chmod +x /usr/local/bin/phing \
    && rm -rf /var/lib/apt/lists/* /usr/share/man/* /usr/share/doc/* /var/cache/* /var/log/* /tmp/*

WORKDIR /srv

COPY docker/php /etc/php/8.2/

EXPOSE 9000

CMD ["php-fpm8.2"]



FROM ${source_image} AS source_image



FROM php AS base

COPY --from=composer/composer:latest-bin /composer /usr/local/bin/composer


FROM base AS dev

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
            php8.2-xdebug \
            bash \
    && adduser --system --no-create-home --uid 1000 --gid 50 docker

COPY docker/php-dev /etc/php/8.2/


FROM base AS source

ENV APP_ENV="prod"

COPY *.json *.lock ./

RUN composer check-platform-reqs \
    && composer install \
        --no-dev \
        --prefer-dist \
        --no-scripts \
        --optimize-autoloader \
        --no-interaction \
    && composer clear-cache

COPY . .

RUN phing app-deploy -Dsymfony.env=prod


FROM php AS prod

ENV APP_ENV="prod"

COPY --from=source_image /srv/ .
