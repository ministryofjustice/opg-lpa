FROM composer:2.9.7@sha256:02062f7719ec9433a9d4256cfba1c792db96dc9db60a4a92e48264c9e166b877 AS composer

FROM php:8.4.17-fpm-alpine3.23@sha256:c0a2345e17cbb925cd49770788838f3e0fac7741d814cbf568d3dd843cfae7b3

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

RUN adduser -D -g '' appuser && chown appuser:appuser /tmp
RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache


RUN apk add --no-cache icu fcgi \
    && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev linux-headers

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="1"

COPY --chown=root:root shared /shared

RUN mkdir -p /service-front/mezzio
COPY --chown=root:root service-front/mezzio/composer.json /service-front/mezzio/composer.json
COPY --chown=root:root service-front/mezzio/composer.lock /service-front/mezzio/composer.lock
RUN ln -s /service-front/mezzio /app

COPY --from=composer /usr/bin/composer /usr/bin/
RUN composer check-platform-reqs -d /service-front/mezzio \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --optimize-autoloader -d /service-front/mezzio

# Core files for the application
COPY --chown=root:root service-front/mezzio/config /service-front/mezzio/config
COPY --chown=root:root service-front/mezzio/src /service-front/mezzio/src
COPY --chown=root:root service-front/mezzio/public /service-front/mezzio/public
COPY --chown=root:root service-front/mezzio/data /service-front/mezzio/data


# PHP config files (reuse the shared service config)
COPY --chown=root:root shared/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY --chown=root:root shared/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/
COPY --chown=root:root shared/docker/app/www.conf /usr/local/etc/php-fpm.d/

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
