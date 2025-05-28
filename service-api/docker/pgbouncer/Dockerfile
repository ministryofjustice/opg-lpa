FROM bitnami/pgbouncer:1.24.1-debian-12-r2@sha256:3b94a8a55fb316ad905047e7807bf1fc28f705244861d435e030a9b48c4c912c

ADD --chmod=444 https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem /etc/ssl/certs/rds-ca-certificates.crt

ENV PGBOUNCER_SERVER_TLS_CA_FILE=/etc/ssl/certs/rds-ca-certificates.crt
