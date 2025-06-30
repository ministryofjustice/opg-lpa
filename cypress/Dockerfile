FROM cypress/included:14.5.0@sha256:a807b075fab789a06d3b4618052bb59574c777093d9537e5587c853294f028b4

WORKDIR /app

RUN apt-get update && \
  apt-get install -y python3-pip

RUN pip3 install boto3

COPY package.json .

RUN npm install .

ENV CYPRESS_VIDEO=false
ENV CYPRESS_RUN_A11Y_TESTS=true

COPY cypress.config.js .
COPY cypress cypress
COPY tests/python-api-client tests/python-api-client
