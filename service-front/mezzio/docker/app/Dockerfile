FROM composer:2.10.1@sha256:7725eb4545c438629ae8bde3ef0bb9a5038ef566126ad878442a69007242d267 AS composer

FROM php:8.4.17-fpm-alpine3.23@sha256:c0a2345e17cbb925cd49770788838f3e0fac7741d814cbf568d3dd843cfae7b3 AS base

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

RUN adduser -D -g '' appuser && chown appuser:appuser /tmp
RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache


RUN apk add --no-cache icu fcgi \
    && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev linux-headers \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install intl \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install opcache \
    && docker-php-ext-install sockets

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="1"

COPY --chown=root:root shared /shared

RUN mkdir -p /service-front/mezzio
COPY --chown=root:root service-front/mezzio/composer.json /service-front/mezzio/composer.json
COPY --chown=root:root service-front/mezzio/composer.lock /service-front/mezzio/composer.lock
# Copy the legacy Application module which is referenced by mezzio's autoloader
COPY --chown=root:root service-front/module /service-front/module
RUN ln -s /service-front/mezzio /app

COPY --from=composer /usr/bin/composer /usr/bin/
RUN composer check-platform-reqs -d /service-front/mezzio \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --optimize-autoloader -d /service-front/mezzio

# Core files for the application
COPY --chown=root:root service-front/mezzio/config /service-front/mezzio/config
COPY --chown=root:root service-front/mezzio/src /service-front/mezzio/src
COPY --chown=root:root service-front/mezzio/public /service-front/mezzio/public
COPY --chown=root:root service-front/mezzio/data /service-front/mezzio/data
COPY --chown=root:root service-front/mezzio/test /service-front/mezzio/test
COPY --chown=root:root service-front/mezzio/content /service-front/mezzio/content


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
    pecl install xdebug ; \
    echo "xdebug.mode = profile" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.discover_client_host = true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    echo "xdebug.idekey = PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    docker-php-ext-enable xdebug ; \
    composer dump-autoload ; \
fi

RUN rm /usr/bin/composer /app/composer.json /app/composer.lock && \
    chown -R root:root /app/vendor

RUN apk del .build-dependencies

USER appuser

VOLUME [ "/tmp" ]

CMD ["php-fpm"]

FROM base AS test-runner

USER "root"
RUN chown -R appuser:appuser /app/vendor

COPY --chown=root:root service-front/mezzio/composer.json /app/composer.json
COPY --chown=root:root service-front/mezzio/composer.lock /app/composer.lock
COPY --from=composer /usr/bin/composer /usr/bin/

RUN composer check-platform-reqs -d /app \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer

RUN chmod -R a+w /tmp
RUN mkdir -m a+w build

USER appuser

COPY --chown=root:root service-front/mezzio/phpunit.xml /app/phpunit.xml
COPY --chown=root:root service-front/mezzio/psalm.xml /app/psalm.xml
