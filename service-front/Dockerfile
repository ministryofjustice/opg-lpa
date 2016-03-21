FROM registry.service.dsd.io/opguk/php-fpm:0.1.130

ADD docker/ca/LPA_ca.crt /usr/local/share/ca-certificates/LPA_ca.crt
RUN /usr/sbin/update-ca-certificates

RUN groupadd webservice && \
    groupadd supervisor

RUN apt-get update && apt-get install -y \
    php5-curl php-pear php5-dev php5-redis

#
#
#

RUN php5enmod mcrypt

RUN echo "expose_php = Off" > /etc/php5/mods-available/do_not_expose_php.ini && \
    php5enmod do_not_expose_php

RUN echo "short_open_tag = On" > /etc/php5/mods-available/allow_php_short_tags.ini
RUN php5enmod allow_php_short_tags

ADD . /app
RUN mkdir -p /srv/opg-lpa-front2/application && \
    mkdir /srv/opg-lpa-front2/application/releases && \
    mkdir /srv/opg-lpa-front2/application/shared && \
    mkdir /srv/opg-lpa-front2/application/shared/log && \
    mkdir /srv/opg-lpa-front2/application/shared/pids && \
    mkdir /srv/opg-lpa-front2/application/shared/system && \
    mkdir /srv/opg-lpa-front2/application/shared/tmp && \
    mkdir /srv/opg-lpa-front2/application/shared/session && \
    chown -R app:app /srv/opg-lpa-front2/application && \
    chmod -R 755 /srv/opg-lpa-front2/application && \
    ln -s /app /srv/opg-lpa-front2/application/current

# Following line has some elements that are required and some that need fixing in the image
RUN mkdir /etc/nginx/app.conf.d/
ADD docker/confd /etc/confd

# Following line needs to be fixed in image
ADD docker/my_init/40-update-php-fpm-environment /etc/my_init.d/40-update-php-fpm-environment

ENV OPG_SERVICE front
