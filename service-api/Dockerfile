FROM registry.service.dsd.io/opguk/php-fpm:0.1.130

RUN groupadd webservice && \
    groupadd supervisor

RUN apt-get update && apt-get install -y \
    php5-curl php-pear php5-dev php5-redis

RUN pecl install mongo && \
    echo "extension=mongo.so" > /etc/php5/mods-available/mongo.ini && \
    php5enmod mongo

RUN php5enmod mcrypt

RUN echo "expose_php = Off" > /etc/php5/mods-available/do_not_expose_php.ini && \
    php5enmod do_not_expose_php

#
#

ADD . /app
RUN mkdir -p /srv/opg-lpa-api2/application && \
    mkdir /srv/opg-lpa-api2/application/releases && \
    chown -R app:app /srv/opg-lpa-api2/application && \
    chmod -R 755 /srv/opg-lpa-api2/application && \
    ln -s /app /srv/opg-lpa-api2/application/current

#
#
ADD docker/confd /etc/confd

ADD docker/my_init/* /etc/my_init.d/

ENV OPG_SERVICE api
