FROM cypress/included:15.15.0@sha256:73954f654de6a085c485fd6cdc6a543e121aa79b64b72b89ddb814fa7ca90a2e

WORKDIR /app

RUN apt-get update && \
  apt-get install -y python3-requests

COPY package.json .
COPY package-lock.json .

RUN npm ci --ignore-scripts .

ENV CYPRESS_VIDEO=false

COPY cypress.config.js .
COPY cypress cypress
COPY tests/python-api-client tests/python-api-client

CMD [ "cypress" ] 
