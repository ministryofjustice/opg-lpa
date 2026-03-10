FROM cypress/included:15.11.0@sha256:3d31c71a3f444c6905a724396ae6fe8c5099133207455fe36d2c6bf38446dc96

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
