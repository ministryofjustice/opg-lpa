FROM cypress/included:15.18.0@sha256:5673defbe5ce3caeaf5eabbc6077c46d448e11fd7a64d4d5bfd269ec63c2a9dd

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
