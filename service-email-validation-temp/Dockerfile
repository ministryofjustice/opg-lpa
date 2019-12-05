FROM php:7.3-alpine

RUN apk add composer \
  zlib-dev \
  icu-dev \
  g++ \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl
# without this, the error returned by email validator is 
# Fatal error: Uncaught LogicException: The Egulias\EmailValidator\Validation\DNSCheckValidation class requires the Intl extension. in /app/vendor/egulias/email-validator/EmailValidator/Validation/DNSCheckValidation.php:25
# Stack trace:
#0 /app/emailcheck.php(13): Egulias\EmailValidator\Validation\DNSCheckValidation->__construct()
#1 {main}
# thrown in /app/vendor/egulias/email-validator/EmailValidator/Validation/DNSCheckValidation.php on line 25
COPY . /app

WORKDIR /app
RUN composer require egulias/email-validator

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
  && php emailcheck.php
