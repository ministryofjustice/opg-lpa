FROM bitnami/pgbouncer:1.24.1-debian-12-r8@sha256:823c47c0615eab9575b5e26f5db711571fca011faa577775ffca6008d13e6f25

ADD --chmod=444 https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem /etc/ssl/certs/rds-ca-certificates.crt

ENV PGBOUNCER_SERVER_TLS_CA_FILE=/etc/ssl/certs/rds-ca-certificates.crt
