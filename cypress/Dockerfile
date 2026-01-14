FROM cypress/included:15.8.1

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
