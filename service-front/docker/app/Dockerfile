FROM composer:2.9.7@sha256:b148074c5cf8e5c564e92baa3f0d2e28ffa0361ede57a03de3bf4b9cf80de54a AS composer

FROM php:8.4-fpm-alpine3.23@sha256:aa3fbc44ad16c4a0ecb35797f31a2259a42c001d7711b649f4916a02deedf6e9 AS base

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
    pecl install xdebug ; \
    echo "xdebug.mode = profile" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.discover_client_host = true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.idekey = PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    docker-php-ext-enable xdebug ; \
    touch /tmp/xdebug.log ; \
    chown appuser:appuser /tmp/xdebug.log ; \
fi

# Remove composer build-time artifacts and secure composer dependencies
RUN rm /usr/bin/composer /app/composer.json /app/composer.lock && \
    chown -R root:root /app/vendor

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

USER appuser

VOLUME [ "/tmp" ]

CMD ["php-fpm"]

FROM base AS test-runner

USER "root"
RUN chown -R appuser:appuser /app/vendor

COPY --chown=root:root service-front/composer.json /app/composer.json
COPY --chown=root:root service-front/composer.lock /app/composer.lock
COPY --from=composer /usr/bin/composer /usr/bin/

RUN composer check-platform-reqs -d /app \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer

RUN chmod -R a+w /tmp
RUN mkdir -m a+w build

USER appuser

COPY --chown=root:root service-front/phpunit.xml /app/phpunit.xml
COPY --chown=root:root service-front/psalm.xml /app/psalm.xml

FROM base AS production

USER root
RUN rm -rf /app/module/Application/tests
USER appuser
