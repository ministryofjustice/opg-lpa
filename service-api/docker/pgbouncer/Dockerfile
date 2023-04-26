FROM bitnami/pgbouncer:1.18.0

RUN wget https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem -O /tmp/global-bundle.pem && \
    cat /tmp/global-bundle.pem >> /opt/bitnami/pgbouncer/etc/ssl/certs/ca-certificates.crt

ENV PGBOUNCER_SERVER_TLS_CA_FILE=/opt/bitnami/pgbouncer/etc/ssl/certs/ca-certificates.crt
