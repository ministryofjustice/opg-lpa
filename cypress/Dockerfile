FROM cypress/included:9.3.1

WORKDIR /app

RUN apt-get update && \
  apt-get install -y python3-pip

RUN pip3 install boto3 requests

RUN npm install --save-dev axe-core \
    cypress-cucumber-preprocessor cypress-plugin-tab cypress-failed-log cli-table@0.3.9 strip-ansi

ENV CYPRESS_VIDEO=false
ENV CYPRESS_RUN_A11Y_TESTS=true

COPY cypress.json .
COPY package.json .
COPY cypress cypress
COPY tests/python-api-client tests/python-api-client
