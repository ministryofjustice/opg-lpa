FROM cypress/included:5.2.0

WORKDIR /root

RUN apt-get install curl

ENV CYPRESS_VIDEO=false

COPY cypress.json .
COPY cypress cypress
