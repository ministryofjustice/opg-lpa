FROM registry.service.dsd.io/opguk/base:latest

ADD docker/ca/LPA_ca.crt /usr/local/share/ca-certificates/LPA_ca.crt
RUN /usr/sbin/update-ca-certificates

RUN groupadd supervisor

RUN apt-get update && apt-get install -y \
    php5-cli php5-dev php5-redis pdftk php5-mcrypt

RUN php5enmod mcrypt

RUN pecl install proctitle-0.1.2 && \
    echo "extension=proctitle.so" > /etc/php5/mods-available/proctitle.ini && \
    php5enmod proctitle

ADD . /app
RUN mkdir -p /srv/opg-lpa-pdf2/application && \
    mkdir /srv/opg-lpa-pdf2/application/releases && \
    mkdir /srv/opg-lpa-pdf2/application/shared && \
    mkdir /srv/opg-lpa-pdf2/application/shared/log && \
    mkdir /srv/opg-lpa-pdf2/application/shared/pids && \
    mkdir /srv/opg-lpa-pdf2/application/shared/system && \
    mkdir /srv/opg-lpa-pdf2/application/shared/tmp && \
    mkdir /srv/opg-lpa-pdf2/application/shared/session && \
    chown -R app:app /srv/opg-lpa-pdf2/application && \
    chmod -R 755 /srv/opg-lpa-pdf2/application && \
    ln -s /app /srv/opg-lpa-pdf2/application/current

ADD docker/default/opg-lpa-pdf2 /etc/default/opg-lpa-pdf2
ADD docker/service/opg-lpa-pdf2 /etc/service/opg-lpa-pdf2
RUN chmod a+x /etc/service/opg-lpa-pdf2/run

ENV OPG_SERVICE pdf
