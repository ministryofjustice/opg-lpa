# Casper JS tests

## Running the tests

Run the following command to run all the tests

```bash
aws-vault exec identity -- docker run -it -v ${PWD}/tests:/mnt/test -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=<PUBLIC_FRONT_URL>" --net=host --rm casperjs:latest ./start.sh 'tests/'
```

To run the S3Monitor on terminal

```bash
aws-vault exec identity -- php S3Monitor.php
```

To run the casper tests in local environment

```bash
docker run -d -e "CASPER_EMAIL_USER=CASPER_EMAIL_USER" -e "CASPER_EMAIL_PASSWORD=CASPER_EMAIL_PASSWORD" -e "BASE_DOMAIN=BASE_DOMAIN" --name casperjs casperjs:latest

docker exec casperjs ./start.sh 'tests/'
```

## Advanced information

When a test suite is run, a service is activated inside the container. The relevant test emails got to S3 and S3 bucket is monitored. This allows the test
suites to activate accounts and reset passwords. The email account needs to be setup for IMAP and should have auto-expunge set to Off.

To login to the docker container.

```bash
aws-vault exec identity -- docker run -it -v ${PWD}/tests:/mnt/test -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=<PUBLIC_FRONT_URL>" --net=host --rm casperjs:latest /usr/bin/env bash
```

Note that once inside anything you do is ephemeral; when you exit and re-enter the state of the container will have been reset.
