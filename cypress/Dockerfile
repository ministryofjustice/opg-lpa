# first section is purely to allow the working of S3Monitor, which is written in php

FROM composer:latest AS composer

COPY tests/composer.json /app/composer.json
COPY tests/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:7.4-cli

ENV DEBIAN_FRONTEND=noninteractive
ENV TERM=linux

RUN apt-get update && \
  apt-get install -y git-core \
  libfreetype6 \
  libfontconfig \
  python \
  libc-client-dev \
  libkrb5-dev \
  openssl \
  procps

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
  docker-php-ext-install -j$(nproc) imap

RUN php /mnt/test/functional/service/S3Monitor.php &

# from here on is actual cypress
FROM cypress/included:5.2.0

WORKDIR /app

RUN npm install --save-dev cypress-axe@0.9.1 axe-core cypress-cucumber-preprocessor cosmiconfig

ENV CYPRESS_VIDEO=false

COPY cypress.json .
COPY package.json .
COPY cypress cypress
