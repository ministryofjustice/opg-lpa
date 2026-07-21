FROM composer:2.10.1@sha256:7725eb4545c438629ae8bde3ef0bb9a5038ef566126ad878442a69007242d267 AS composer

FROM php:8.4.17-fpm-alpine3.23@sha256:41cc31d474f8444ee260b5e8a73d2b00a1c3a437cfc57c2d2d0cbc22bc5a3925 AS base

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

RUN adduser -D -g '' appuser && chown appuser:appuser /tmp
RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

RUN apk add --no-cache icu fcgi \
    && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev linux-headers \
    && docker-php-ext-install intl \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install opcache \
    && docker-php-ext-install sockets;

RUN wget -q --timeout=10 -O /dev/null \
        "https://ghcr.io/token?service=ghcr.io&scope=repository:php/pie:pull" \
    || { echo ""; \
         echo "ERROR: The PIE (PHP Installation Engine) registry at ghcr.io is not reachable."; \
         echo "This build requires the PIE binary from ghcr.io/php/pie to install PHP extensions."; \
         echo "Check GitHub Container Registry status: https://www.githubstatus.com/"; \
         echo ""; \
         exit 1; }

COPY --from=ghcr.io/php/pie:bin@sha256:65d91cecb73cb1fd03ae2a892d8ec76b25d34468532c0c2032afa9c412cf8b43 /pie /usr/bin/pie

RUN pie install --no-cache phpredis/phpredis:^6.0

# Default for AWS. Should be set to 1 for local development.
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

ARG OPG_LPA_COMMON_APP_VERSION="dev"
ENV OPG_LPA_COMMON_APP_VERSION=${OPG_LPA_COMMON_APP_VERSION}

COPY --chown=root:root shared /shared

COPY --chown=root:root service-front/composer.json /app/composer.json
COPY --chown=root:root service-front/composer.lock /app/composer.lock

COPY --from=composer /usr/bin/composer /usr/bin/
RUN composer check-platform-reqs -d /app \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --optimize-autoloader -d /app

# Core files for the application
COPY --chown=root:root service-front/config /app/config
COPY --chown=root:root service-front/src /app/src
COPY --chown=root:root service-front/public /app/public
COPY --chown=root:root service-front/data /app/data
COPY --chown=root:root service-front/content /app/content


# PHP config files (reuse the shared service config)
COPY --chown=root:root shared/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY --chown=root:root shared/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/
COPY --chown=root:root shared/docker/app/www.conf /usr/local/etc/php-fpm.d/

COPY --chown=root:root scripts/containers/health-check.sh /usr/local/bin/health-check.sh
RUN chmod +x /usr/local/bin/health-check.sh

WORKDIR /app

ARG ENABLE_XDEBUG=0
RUN if [ "$ENABLE_XDEBUG" = "1" ] ; then \
    chmod a+w /tmp ; \
    chown -R appuser:appuser /app ; \
    apk add --no-cache --virtual .build-dependencies linux-headers pcre-dev ${PHPIZE_DEPS} ; \
    pie install xdebug/xdebug:^3.4 ; \
    echo "xdebug.mode = profile" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.discover_client_host = true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.idekey = PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    composer dump-autoload ; \
fi

RUN rm /usr/bin/composer /app/composer.json /app/composer.lock && \
    chown -R root:root /app/vendor

RUN apk del .build-dependencies

USER appuser

VOLUME [ "/tmp" ]

CMD ["php-fpm"]

FROM base AS composer-tools

USER root
COPY --from=composer /usr/bin/composer /usr/bin/
WORKDIR /app

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

COPY --chown=root:root service-front/test /app/test
COPY --chown=root:root service-front/phpunit.xml /app/phpunit.xml
COPY --chown=root:root service-front/psalm.xml /app/psalm.xml
COPY --chown=root:root service-front/psalm-baseline.xml /app/psalm-baseline.xml
