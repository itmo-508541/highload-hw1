ARG source_image=scratch
FROM debian:bullseye-20220711-slim AS php

ENV TERM linux
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
            apt-transport-https gnupg ca-certificates curl \
            netcat \
    && echo "deb https://packages.sury.org/php/ bullseye main" >> /etc/apt/sources.list.d/sury.org.list \
    && curl -sS https://packages.sury.org/php/apt.gpg | apt-key add - \
    && apt-get update \
    && apt-get install -y --no-install-recommends --allow-unauthenticated \
            php8.0-curl \
            php8.0-fpm \
            php8.0-gd \
            php8.0-intl \
            php8.0-mbstring \
            php8.0-mysql \
            php8.0-xml \
            php8.0-zip \
    && apt-get purge --autoremove -y gnupg \
    && apt-get clean -y \
    && curl -sS -L -o /usr/local/bin/phing http://www.phing.info/get/phing-latest.phar \
    && chmod +x /usr/local/bin/phing \
    && rm -rf /var/lib/apt/lists/* /usr/share/man/* /usr/share/doc/* /var/cache/* /var/log/* /tmp/*

WORKDIR /srv

COPY docker/php /etc/php/8.0/

EXPOSE 9000

CMD ["php-fpm8.0"]



FROM ${source_image} AS source_image



FROM php AS base

COPY --from=jakzal/phpqa:1.76-php8.0-alpine /tools/phpunit /tools/composer /usr/local/bin/



FROM base AS dev

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
            php8.0-xdebug \
            bash \
    && adduser --system --no-create-home --uid 1000 --gid 50 docker \
    && mkdir -p /tmp/sessions \
    && chmod -R 777 /tmp/sessions

COPY docker/php-dev /etc/php/8.0/


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

RUN chmod -R -x+X . \
    && chmod 755 bin/console \
    && phing app-deploy -Dsymfony.env=prod



FROM php AS prod

ENV APP_ENV="prod"

COPY --from=source_image /srv/ .
