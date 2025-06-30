FROM bitnami/pgbouncer:1.24.1-debian-12-r5@sha256:4fd476725d8ce0b0b496edaf644639d0895657fd8214fa0f3d9eda888e1e1505

ADD --chmod=444 https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem /etc/ssl/certs/rds-ca-certificates.crt

ENV PGBOUNCER_SERVER_TLS_CA_FILE=/etc/ssl/certs/rds-ca-certificates.crt
