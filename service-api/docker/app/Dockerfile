FROM composer:2.9.3@sha256:631d7c9ef9cca5fd2f87f691bb9175c89df3c69fbced73148314c11df270a108 AS composer

FROM php:8.4.13-fpm-alpine3.22@sha256:aee4a044beefc7d88d0419aee8825491e02391f37707b8fc0d8edba1a29c1165

COPY --from=mlocati/php-extension-installer:2@sha256:b17b8107fe8480d5f88c7865b83bb121a344876272eb6b7c9e9f331c931695be /usr/bin/install-php-extensions /usr/bin/

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

RUN adduser -D -g '' appuser && chown appuser:appuser /tmp

RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

RUN apk add --no-cache postgresql-libs icu fcgi \
    && apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS postgresql-dev icu-dev linux-headers \
    && docker-php-ext-install pgsql pdo_pgsql \
    && docker-php-ext-install opcache \
    && docker-php-ext-install intl \
    && docker-php-ext-install sockets \
    && install-php-extensions gmp \
    && rm /usr/bin/install-php-extensions

# Default for AWS. Should be set to 1 for local development.
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

# Install composer dependencies
COPY --chown=root:root service-api/composer.json /app/composer.json
COPY --chown=root:root service-api/composer.lock /app/composer.lock
COPY --from=composer /usr/bin/composer /usr/bin/
RUN composer check-platform-reqs --no-dev -d /app \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --no-dev --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer

ARG OPG_LPA_COMMON_APP_VERSION
ENV OPG_LPA_COMMON_APP_VERSION=${OPG_LPA_COMMON_APP_VERSION}


# Core files for the application
COPY --chown=root:root service-api/db/migrations /app/db/migrations
COPY --chown=root:root service-api/config /app/config
COPY --chown=root:root service-api/module /app/module
COPY --chown=root:root service-api/public /app/public

# Shared code
COPY --chown=root:root shared /shared

# PHP config files
COPY --chown=root:root shared/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY --chown=root:root shared/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/
COPY --chown=root:root shared/docker/app/www.conf /usr/local/etc/php-fpm.d/

# Migration script
COPY --chown=root:root service-api/docker/app/db-migrations.sh /usr/local/bin/db-migrations.sh
COPY --chown=root:root service-api/phinx.php /app/phinx.php
COPY --chown=root:root scripts/containers/health-check.sh /usr/local/bin/health-check.sh

RUN chmod +x /usr/local/bin/db-migrations.sh /usr/local/bin/health-check.sh

WORKDIR /app

# Enable debug if needed. Should only be used locally
ARG ENABLE_XDEBUG=0
RUN if [ "$ENABLE_XDEBUG" = "1" ] ; then \
      pecl install xdebug ; \
      echo "xdebug.mode = develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.discover_client_host = true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.idekey = PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      docker-php-ext-enable xdebug ; \
    fi ;


# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

USER appuser

VOLUME [ "/tmp" ]

CMD ["/bin/sh", "-c", "/usr/local/bin/db-migrations.sh && php-fpm"]
