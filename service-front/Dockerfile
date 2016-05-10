FROM registry.service.dsd.io/opguk/php-fpm:0.1.130

RUN groupadd webservice && \
    groupadd supervisor

RUN apt-get update && apt-get install -y \
    php5-curl php-pear php5-dev

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
    chown -R app:app /srv/opg-lpa-front2/application && \
    chmod -R 755 /srv/opg-lpa-front2/application && \
    ln -s /app /srv/opg-lpa-front2/application/current

# Following line has some elements that are required and some that need fixing in the image
RUN mkdir /etc/nginx/app.conf.d/
ADD docker/confd /etc/confd

ADD docker/my_init/* /etc/my_init.d/

ENV OPG_SERVICE front
