FROM composer:2.3 AS composer

RUN adduser -D -g '' appuser

COPY --chown=appuser:appuser service-api/composer.json /app/composer.json
COPY --chown=appuser:appuser service-api/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:8.0-fpm-alpine3.15

RUN adduser -D -g '' appuser

RUN apk add --upgrade apk-tools
RUN apk upgrade --available
# Postgres lib needs to remain in the container
RUN apk add --update --no-cache postgresql-libs

# Postgres dev lib is temporary
RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS postgresql-dev \
  && docker-php-ext-install pgsql pdo_pgsql \
  && docker-php-ext-install bcmath \
  && docker-php-ext-install opcache

# Default for AWS. Should be set to 1 for local development.
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

# Enable debug if needed. Should only be used locally
ARG ENABLE_XDEBUG=0
RUN if [[ $ENABLE_XDEBUG = 1 ]] ; then \
      pecl install xdebug ; \
      docker-php-ext-enable xdebug ; \
      echo "xdebug.mode = develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.discover_client_host = true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.idekey = PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    fi ;

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

COPY --chown=appuser:appuser service-api /app
COPY --chown=appuser:appuser shared/logging /shared/logging
COPY --chown=appuser:appuser --from=composer /app/vendor /app/vendor
COPY --chown=appuser:appuser service-api/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY --chown=appuser:appuser service-api/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/

RUN chmod +x /app/docker/app/db-migrations.sh

WORKDIR /app

USER appuser

CMD  /app/docker/app/db-migrations.sh && php-fpm
