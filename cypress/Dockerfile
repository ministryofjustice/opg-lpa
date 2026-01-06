FROM cypress/included:15.7.1

WORKDIR /app

RUN apt-get update && \
  apt-get install -y python3-requests

COPY package.json .
COPY package-lock.json .

RUN npm ci --ignore-scripts .

ENV CYPRESS_VIDEO=false

COPY cypress.config.js .
COPY cypress/cypress_start.sh .
COPY cypress cypress
COPY tests/python-api-client tests/python-api-client

#CMD [ "CYPRESS_userNumber=`python3 cypress/user_number.py`", "cypress", "run", "--project", "/app", "-e", "stepDefinitions=\"/app/cypress/e2e/common/*.js\""]
#CMD [ "CYPRESS_userNumber=`python3 cypress/user_number.py`", "cypress" ] 
CMD [ "cypress" ] 
