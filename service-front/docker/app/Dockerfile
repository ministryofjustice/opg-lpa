FROM composer:2.9.4@sha256:22a95bd2bc3cf6ff19550a246208138d35f0015602db3d67dc6841945324acc1 AS composer

FROM php:8.4.17-fpm-alpine3.23@sha256:c0a2345e17cbb925cd49770788838f3e0fac7741d814cbf568d3dd843cfae7b3

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

RUN adduser -D -g '' appuser && chown appuser:appuser /tmp
RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

# The --virtual flag puts build dependencies into their own
# directory to make them easier to remove en masse
RUN apk add --no-cache icu fcgi \
    && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev linux-headers \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install intl \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install opcache \
    && docker-php-ext-install sockets

# Default for AWS. Should be set to 1 for local development.
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

# Install composer dependencies
COPY --chown=root:root service-front/composer.json /app/composer.json
COPY --chown=root:root service-front/composer.lock /app/composer.lock
COPY --from=composer /usr/bin/composer /usr/bin/
RUN composer check-platform-reqs --no-dev -d /app \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --no-dev --optimize-autoloader -d /app

# Core files for the application
COPY --chown=root:root service-front/assets /app/assets
COPY --chown=root:root service-front/config /app/config
COPY --chown=root:root service-front/content /app/content
COPY --chown=root:root service-front/module /app/module
COPY --chown=root:root service-front/public /app/public

# Shared code
COPY --chown=root:root shared /shared

# PHP config files
COPY --chown=root:root shared/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY --chown=root:root shared/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/
COPY --chown=root:root shared/docker/app/www.conf /usr/local/etc/php-fpm.d/

COPY --chown=root:root scripts/containers/health-check.sh /usr/local/bin/health-check.sh

RUN chmod +x /usr/local/bin/health-check.sh

WORKDIR /app

# Enable debug and test coverage tooling if needed. Should only be used locally.
ARG ENABLE_XDEBUG=0
RUN if [ "$ENABLE_XDEBUG" = "1" ] ; then \
    chmod a+w /tmp ; \
    chown -R appuser:appuser /app ; \
    apk add --no-cache --virtual .build-dependencies linux-headers pcre-dev ${PHPIZE_DEPS} ; \
    pecl install xdebug ; \
    echo "xdebug.mode = profile" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.discover_client_host = true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.idekey = PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    docker-php-ext-enable xdebug ; \
    composer require phpunit/php-code-coverage:9.2.* ; \
    composer dump-autoload ; \
fi

# Remove composer build-time artifacts and secure composer dependencies
RUN rm /usr/bin/composer /app/composer.json /app/composer.lock && \
    chown -R root:root /app/vendor

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

USER appuser

VOLUME [ "/tmp" ]

CMD ["php-fpm"]
