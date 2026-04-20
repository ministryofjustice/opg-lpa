FROM cypress/included:15.13.1@sha256:ea63f59ebb06419dbb6298635adcb1c84ed4935cae42111c60dc0fa37865834b

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
