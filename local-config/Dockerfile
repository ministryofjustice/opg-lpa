FROM python:alpine

RUN pip install awscli && \
    apk add zip jq

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxcnunes/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

# these 2 lines should be mirrored by the volumes set up in docker-compose.yml
COPY ./local-config/start.sh /app/
COPY ./service-perfplat/src/server/ /perfplat-src/

WORKDIR /app

CMD chmod +x /app/*.sh \
  && /app/start.sh
