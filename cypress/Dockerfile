FROM cypress/included:15.20.1@sha256:327d62ee6d1c9bd7b4b490bb2772c1b70d37d56647953a4ec3105a939b7c61ff

WORKDIR /app

RUN apt-get update && \
  apt-get install -y python3-requests

COPY package.json .
COPY package-lock.json .

RUN npm ci --ignore-scripts .

ENV CYPRESS_VIDEO=false

COPY cypress.config.js .
COPY .cypress-cucumber-preprocessorrc.json .
COPY cypress cypress
COPY tests/python-api-client tests/python-api-client

RUN chown -R node:node /app

USER node

CMD [ "cypress" ]
