FROM debian:trixie-slim

RUN apt update \
    && apt install -y git \
    && apt clean

COPY ./ssh_config /etc/ssh/ssh_config

RUN chmod 644 /etc/ssh/ssh_config \
    && git config --global user.name "John Doe" \
    && git config --global user.email "doe@example.com"
