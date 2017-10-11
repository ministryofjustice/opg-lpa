FROM registry.service.opg.digital/opg-base-1604

RUN groupadd supervisor

RUN apt-get install -y php-dev pkg-config

RUN pecl install proctitle-0.1.2 && \
    echo "extension=proctitle.so" > /etc/php/7.0/mods-available/proctitle.ini && \
    phpenmod proctitle

# Add application logging config(s)
ADD docker/beaver.d /etc/beaver.d

ADD . /app
RUN mkdir -p /srv/opg-lpa-pdf2/application && \
    mkdir /srv/opg-lpa-pdf2/application/releases && \
    chown -R app:app /srv/opg-lpa-pdf2/application && \
    chmod -R 755 /srv/opg-lpa-pdf2/application && \
    ln -s /app /srv/opg-lpa-pdf2/application/current

RUN mkdir /etc/service/opg-lpa-pdf2/
ADD docker/confd /etc/confd

ENV OPG_SERVICE pdf
