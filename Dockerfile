ARG source_image=scratch
FROM debian:buster-20210511-slim AS php

ENV TERM linux
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
            apt-transport-https gnupg ca-certificates curl \
            netcat \
    && echo "deb https://packages.sury.org/php/ buster main" >> /etc/apt/sources.list.d/sury.org.list \
    && curl -sS https://packages.sury.org/php/apt.gpg | apt-key add - \
    && apt-get update \
    && apt-get install -y --no-install-recommends --allow-unauthenticated \
            php7.4-curl \
            php7.4-fpm \
            php7.4-intl \
            php7.4-mbstring \
            php7.4-mysql \
            php7.4-xml \
            php7.4-zip \
    && apt-get purge --autoremove -y gnupg \
    && apt-get clean -y \
    && curl -sS -L -o /usr/local/bin/phing http://www.phing.info/get/phing-latest.phar \
    && chmod +x /usr/local/bin/phing \
    && rm -rf /var/lib/apt/lists/* /usr/share/man/* /usr/share/doc/* /var/cache/* /var/log/* /tmp/*

WORKDIR /srv

COPY docker/php /etc/php/7.4/

EXPOSE 9000

CMD ["php-fpm7.4"]



FROM ${source_image} AS source_image



FROM php AS base

COPY --from=jakzal/phpqa:1.56.1-php7.4-alpine /tools/phpunit-7 /tools/composer /usr/local/bin/



FROM base AS dev

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
            php7.4-xdebug \
            bash \
    && adduser --system --no-create-home --uid 1000 --gid 50 docker \
    && mkdir -p /tmp/sessions \
    && chmod -R 777 /tmp/sessions



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
