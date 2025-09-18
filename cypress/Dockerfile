FROM cypress/included:14.5.3@sha256:ddf238e00773ae09c4e034ccf481adb1c8603d977f7abddf640e2a866f8939eb

WORKDIR /app

RUN apt-get update && \
  apt-get install -y python3-pip

RUN pip3 install boto3

COPY package.json .

RUN npm ci --ignore-scripts .

ENV CYPRESS_VIDEO=false
ENV CYPRESS_RUN_A11Y_TESTS=true

COPY cypress.config.js .
COPY cypress cypress
COPY tests/python-api-client tests/python-api-client
