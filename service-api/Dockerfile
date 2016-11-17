FROM registry.service.opg.digital/opguk/php-fpm:0.1.200

RUN groupadd webservice && \
    groupadd supervisor

RUN apt-get update && apt-get install -y \
    php5-curl php-pear php5-dev

RUN pecl install mongo && \
    echo "extension=mongo.so" > /etc/php5/mods-available/mongo.ini && \
    php5enmod mongo

RUN php5enmod mcrypt

RUN echo "expose_php = Off" > /etc/php5/mods-available/do_not_expose_php.ini && \
    php5enmod do_not_expose_php

# Add application logging config(s)
ADD docker/beaver.d /etc/beaver.d

ADD . /app
RUN mkdir -p /srv/opg-lpa-api2/application && \
    mkdir /srv/opg-lpa-api2/application/releases && \
    chown -R app:app /srv/opg-lpa-api2/application && \
    chmod -R 755 /srv/opg-lpa-api2/application && \
    ln -s /app /srv/opg-lpa-api2/application/current

ADD docker/my_init/* /etc/my_init.d/
ADD docker/certificates/* /usr/local/share/ca-certificates/

ADD docker/bin/update-ca-certificates /usr/sbin/update-ca-certificates
RUN chmod 755 /usr/sbin/update-ca-certificates; sync; /usr/sbin/update-ca-certificates --verbose

ENV OPG_SERVICE api
