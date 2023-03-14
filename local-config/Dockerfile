FROM python:3.11-alpine

RUN pip install awscli

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxcnunes/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

# COPY commands should mirror volumes set up in docker-compose.yml
COPY ./start.sh /app/

WORKDIR /app

CMD chmod +x /app/*.sh \
  && /app/start.sh