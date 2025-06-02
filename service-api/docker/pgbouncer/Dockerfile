FROM bitnami/pgbouncer:1.24.1-debian-12-r3@sha256:3b03f1169a35f1d492a786f588660170c59a8e71e8e69b4c97c66278c0e08431

ADD --chmod=444 https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem /etc/ssl/certs/rds-ca-certificates.crt

ENV PGBOUNCER_SERVER_TLS_CA_FILE=/etc/ssl/certs/rds-ca-certificates.crt
