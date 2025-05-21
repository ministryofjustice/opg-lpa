FROM bitnami/pgbouncer:1.24.1-debian-12-r2

USER root

# Pull the newest packages from unstable Debian
# TODO: update base image once update to Debian rolls out
RUN echo "deb http://deb.debian.org/debian sid main" > /etc/apt/sources.list.d/sid.list \
    && apt-get update \
    && apt-get -y -t sid install libc6 libc6-dev libc-bin \
    && rm /etc/apt/sources.list.d/sid.list \
    && apt-get clean \ 
    && rm -rf /var/lib/apt/lists/*

USER 1001

ADD --chmod=444 https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem /etc/ssl/certs/rds-ca-certificates.crt

ENV PGBOUNCER_SERVER_TLS_CA_FILE=/etc/ssl/certs/rds-ca-certificates.crt
