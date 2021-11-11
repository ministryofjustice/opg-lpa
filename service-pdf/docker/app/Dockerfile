FROM composer:2.1.11 AS composer

COPY service-pdf/composer.json /app/composer.json
COPY service-pdf/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:7.4-cli-alpine

RUN apk add --upgrade apk-tools
RUN apk upgrade --available

RUN apk update \
    && apk add --no-cache openjdk8-jre gcc make musl-dev pkgconfig bash\
    && apk add --no-cache --update --virtual buildDeps autoconf \
    && pecl install xdebug \
    && pecl clear-cache \
    && apk del buildDeps
WORKDIR /usr/local/bin/
# This is version 3.2.1 of this tool, we will need to check this regularly.
# PDFTK does have a community based docker container, but the version is not up to date.

RUN wget https://gitlab.com/pdftk-java/pdftk/-/jobs/812582458/artifacts/raw/build/libs/pdftk-all.jar

COPY service-pdf/bin/pdftk pdftk
RUN chmod +x pdftk

COPY shared/logging /shared/logging
COPY service-pdf /app
COPY --from=composer /app/vendor /app/vendor

WORKDIR /app

CMD /app/bin/start.sh
