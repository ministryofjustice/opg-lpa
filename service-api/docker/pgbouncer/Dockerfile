FROM bitnami/pgbouncer:1.24.1-debian-12-r2

ADD --chmod=444 https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem /etc/ssl/certs/rds-ca-certificates.crt

ENV PGBOUNCER_SERVER_TLS_CA_FILE=/etc/ssl/certs/rds-ca-certificates.crt
