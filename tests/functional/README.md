# Casper JS functional tests

## Running the functional tests

Run the following command in CI to run all the tests

```bash
aws-vault exec identity -- docker run -it -v ${PWD}/tests:/mnt/test -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=<PUBLIC_FRONT_URL>" --net=host --rm casperjs:latest ./start.sh 'tests/'
```

Running the tests with start.sh automatically runs S3Monitor. If for some reason you need to individually run the S3Monitor on terminal you can do :
By specifying the volume with -v,  we can change edit tests on the host machine and not constantly have to rebuild the docker container

```bash
aws-vault exec identity -- php S3Monitor.php
```

To run the tests in local environment

```bash
aws-vault exec identity -- docker run -it -v ${PWD}/tests:/mnt/test -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=localhost:7002" --network="host" --rm casperjs:latest ./start.sh 'tests/'
```

the old way of doing this (left here for historical reasons) was:

```bash
docker run -d -e "CASPER_EMAIL_USER=CASPER_EMAIL_USER" -e "CASPER_EMAIL_PASSWORD=CASPER_EMAIL_PASSWORD" -e "BASE_DOMAIN=BASE_DOMAIN" --name casperjs casperjs:latest
docker exec casperjs ./start.sh 'tests/'
```

that old way, some tests will fail though, because S3Monitor only works via aws-vault, because needs to talk to real S3 

## Advanced information

When a test suite is run, a service is activated inside the container. The relevant test emails got to S3 and S3 bucket is monitored. This allows the test
suites to activate accounts and reset passwords. The email account needs to be setup for IMAP and should have auto-expunge set to Off.

It can be useful to start up a shell to debug failing tests.  To login to the docker container:

```bash
aws-vault exec identity -- docker run -it -v ${PWD}/tests:/mnt/test -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=<PUBLIC_FRONT_URL>" --net=host --rm casperjs:latest /usr/bin/env bash
```

Note that once inside anything you do is ephemeral; when you exit and re-enter the state of the container will have been reset.
