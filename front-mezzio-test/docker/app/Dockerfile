FROM php:8.4.17-fpm-alpine3.23@sha256:c0a2345e17cbb925cd49770788838f3e0fac7741d814cbf568d3dd843cfae7b3

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

RUN adduser -D -g '' appuser && chown appuser:appuser /tmp

COPY --chown=root:root front-mezzio-test /app

# Default for AWS. Should be set to 1 for local development.
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="1"

# PHP config files
COPY --chown=root:root shared/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY --chown=root:root shared/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/
COPY --chown=root:root shared/docker/app/www.conf /usr/local/etc/php-fpm.d/

WORKDIR /app

USER appuser

VOLUME [ "/tmp" ]

CMD ["php-fpm"]
