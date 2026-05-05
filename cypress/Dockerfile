FROM cypress/included:15.14.2@sha256:9f27bf8330595fe44dc3ba9c0c96cff1618d24241bd9f95a470f8f6e2ecfba7e

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
