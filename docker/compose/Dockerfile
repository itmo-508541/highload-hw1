FROM docker:19.03.13

ENV CRYPTOGRAPHY_DONT_BUILD_RUST 1

RUN apk add --no-cache python3 py-pip \
        && apk add --no-cache --virtual .docker-compose-deps python3-dev libffi-dev openssl-dev gcc libc-dev rust cargo make \
        && pip3 install docker-compose \
        && apk del .docker-compose-deps
