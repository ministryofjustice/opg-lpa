FROM composer:2.9.2@sha256:8f3b7d97cd7436dae41d6af0dd6a53853779040ecac26e65544fc31746035705 AS composer

FROM php:8.4.13-fpm-alpine3.22@sha256:aee4a044beefc7d88d0419aee8825491e02391f37707b8fc0d8edba1a29c1165

COPY --from=mlocati/php-extension-installer:2@sha256:4ac3eefdb28ddbc07611c31dfe5353eb7f5e072c0d653bdd915903afa984dc89 /usr/bin/install-php-extensions /usr/bin/

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

RUN adduser -D -g '' appuser && chown appuser:appuser /tmp

RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

RUN apk add --no-cache icu fcgi \
    && apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev \
    && docker-php-ext-install intl \
    && install-php-extensions gmp \
    && rm /usr/bin/install-php-extensions

# Install composer dependencies
COPY --chown=root:root service-admin/composer.json /app/composer.json
COPY --chown=root:root service-admin/composer.lock /app/composer.lock
COPY --from=composer /usr/bin/composer /usr/bin/
RUN composer check-platform-reqs --no-dev -d /app \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --no-dev --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer


COPY --chown=root:root service-admin/config /app/config
COPY --chown=root:root service-admin/public /app/public
COPY --chown=root:root service-admin/src /app/src

COPY --chown=root:root shared /shared

COPY --chown=root:root shared/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY --chown=root:root shared/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/
COPY --chown=root:root shared/docker/app/www.conf /usr/local/etc/php-fpm.d/

COPY --chown=root:root scripts/containers/health-check.sh /usr/local/bin/health-check.sh

RUN chmod +x /usr/local/bin/health-check.sh

WORKDIR /app

# Enable debug if needed. Should only be used locally
ARG ENABLE_XDEBUG=0
RUN if [ "$ENABLE_XDEBUG" = 1 ] ; then \
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

CMD ["php-fpm"]
