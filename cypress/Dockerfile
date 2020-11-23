FROM cypress/included:5.2.0

WORKDIR /root

RUN npm install --save-dev cypress-axe axe-core

ENV CYPRESS_VIDEO=false

COPY cypress.json .
COPY cypress cypress
