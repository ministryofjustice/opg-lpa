FROM cypress/included:7.6.0

WORKDIR /app

RUN apt-get update && \
  apt-get install -y python3-pip

RUN pip3 install boto3 requests

RUN npm install --save-dev axe-core \
    cypress-cucumber-preprocessor cypress-plugin-tab

ENV CYPRESS_VIDEO=false
ENV CYPRESS_RUN_A11Y_TESTS=true

COPY cypress.json .
COPY package.json .
COPY cypress cypress
COPY tests/python-api-client tests/python-api-client
