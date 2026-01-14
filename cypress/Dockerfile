FROM cypress/included:15.9.0@sha256:91111352c3adf4a0ff5bee6f3f49af56a66deb81d74a784dbb68bc98a3fbd26e

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
