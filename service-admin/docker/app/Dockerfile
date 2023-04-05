FROM php:8.2.3-fpm-alpine3.17

RUN adduser -D -g '' appuser

RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

RUN apk add --no-cache icu fcgi \
    && apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev \
    && docker-php-ext-install intl \
    && docker-php-ext-install bcmath

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

# Install composer dependencies
COPY --chown=root:root service-admin/composer.json /app/composer.json
COPY --chown=root:root service-admin/composer.lock /app/composer.lock
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer --version 2.5.5 \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --no-dev --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

COPY --chown=root:root service-admin/config /app/config
COPY --chown=root:root service-admin/public /app/public
COPY --chown=root:root service-admin/src /app/src

COPY --chown=root:root shared /shared

COPY --chown=root:root shared/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY --chown=root:root shared/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/

COPY --chown=root:root scripts/containers/health-check.sh /usr/local/bin/health-check.sh

RUN chmod +x /usr/local/bin/health-check.sh

WORKDIR /app

USER appuser

CMD php-fpm
