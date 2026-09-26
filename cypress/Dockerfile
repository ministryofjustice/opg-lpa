FROM cypress/included:15.21.1@sha256:8d874a8e5457459c7b8f185f797e46c299f7d0584c9fbda0b0ca23b3e85be749

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
