FROM bitnamilegacy/pgbouncer:1.24.1-debian-12-r10@sha256:4315f5019db0fa37fbfb86eab1fd272d7d8b9b847dc80b113f29287fbf195d94

ADD --chmod=444 https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem /etc/ssl/certs/rds-ca-certificates.crt

ENV PGBOUNCER_SERVER_TLS_CA_FILE=/etc/ssl/certs/rds-ca-certificates.crt
