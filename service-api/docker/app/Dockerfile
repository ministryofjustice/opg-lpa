FROM composer:2.1.11 AS composer

COPY service-api/composer.json /app/composer.json
COPY service-api/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:8.0-fpm-alpine3.14

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

# Install debug if needed. Should only be used locally
ARG INSTALL_XDEBUG=0
RUN if [[ $INSTALL_XDEBUG = 1 ]] ; then \
      pecl install xdebug ; \
    fi ;

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

COPY service-api /app
COPY shared/logging /shared/logging
COPY --from=composer /app/vendor /app/vendor
COPY service-api/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-api/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/

WORKDIR /app

# On startup, enable xdebug if required. Note this would require the container to have been built with INSTALL_DEBUG set
CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug; \
    echo "xdebug.mode = develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.discover_client_host = true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.idekey = PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
) \
  && chmod +x /app/docker/app/db-migrations.sh && /app/docker/app/db-migrations.sh \
  && php-fpm
