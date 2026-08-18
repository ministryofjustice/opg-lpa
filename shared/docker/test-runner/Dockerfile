FROM composer:2.10.2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040 AS composer

FROM php:8.4-fpm-alpine3.23@sha256:6397578dd6209a93c22ae5fc06cd48d8e4c3aaea94f10f2d65ceb6f80678c18e AS base

RUN adduser -D -g '' appuser && chown appuser:appuser /tmp
RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

COPY --from=ghcr.io/php/pie:bin@sha256:65d91cecb73cb1fd03ae2a892d8ec76b25d34468532c0c2032afa9c412cf8b43 /pie /usr/bin/pie

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS linux-headers && \
  pie install xdebug/xdebug:^3.4 && \
  docker-php-ext-install sockets &&\
  touch /tmp/xdebug.log &&\
  chown appuser:appuser /tmp/xdebug.log

# Install composer dependencies
COPY --chown=root:root shared/composer.json /shared/composer.json
COPY --chown=root:root shared/composer.lock /shared/composer.lock
COPY --from=composer /usr/bin/composer /usr/bin/
RUN composer check-platform-reqs -d /shared \
  && composer install --prefer-dist --no-interaction --no-cache --no-scripts --optimize-autoloader -d /shared

# Shared files
COPY --chown=root:root shared/module /shared/module
COPY --chown=root:root shared/src /shared/src
COPY --chown=root:root shared/phpunit.xml /shared/phpunit.xml
COPY --chown=root:root shared/psalm.xml /shared/psalm.xml

WORKDIR /shared

# Remove composer build-time artifacts and secure composer dependencies
RUN rm /usr/bin/composer /shared/composer.json /shared/composer.lock && \
  chown -R root:root /shared/vendor

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

FROM base AS composer-tools

USER root
COPY --from=composer /usr/bin/composer /usr/bin/
WORKDIR /shared

FROM base AS test-runner
