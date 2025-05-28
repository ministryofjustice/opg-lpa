FROM python:3.13.3-alpine3.21@sha256:452682e4648deafe431ad2f2391d726d7c52f0ff291be8bd4074b10379bb89ff

RUN pip install awscli

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxclaus/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

# COPY commands should mirror volumes set up in docker-compose.yml
COPY ./start.sh /app/

WORKDIR /app

CMD chmod +x /app/*.sh \
  && /app/start.sh
