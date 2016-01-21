FROM registry.service.dsd.io/opguk/php-fpm:0.1.130

RUN apt-get update && apt-get install -y \
    php5-mcrypt php5-curl php-pear php5-dev php5-redis

ADD . /app

ENV OPG_SERVICCE pdf
