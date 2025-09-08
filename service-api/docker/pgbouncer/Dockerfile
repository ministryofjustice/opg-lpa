FROM bitnamilegacy/pgbouncer:1.24.1-debian-12-r10@sha256:32c2aefdd8c144f08ee8a0776d4a17387e34f1b77dd9ac8ce92af259d4a7c632

ADD --chmod=444 https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem /etc/ssl/certs/rds-ca-certificates.crt

ENV PGBOUNCER_SERVER_TLS_CA_FILE=/etc/ssl/certs/rds-ca-certificates.crt
