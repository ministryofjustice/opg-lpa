FROM composer:2.9.7@sha256:dc292c5c0f95f526b051d4c341bf08e7e2b18504c74625e3203d7f123050e318 AS composer

FROM php:8.4-fpm-alpine3.23@sha256:aa3fbc44ad16c4a0ecb35797f31a2259a42c001d7711b649f4916a02deedf6e9 AS base

RUN adduser -D -g '' appuser && chown appuser:appuser /tmp
RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS linux-headers && \
  pecl install xdebug && \
  docker-php-ext-install sockets &&\
  docker-php-ext-enable xdebug &&\
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

FROM base AS test-runner
