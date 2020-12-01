# Casper JS functional tests

## Running the functional tests

Run the following command in CI to run all the tests

```bash
aws-vault exec identity -- docker run -it -v ${PWD}/tests:/mnt/test -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=<PUBLIC_FRONT_URL>" --net=host --rm casperjs:latest ./start.sh 'tests/'
```

By specifying the volume with -v,  we can change edit tests on the host machine and not constantly have to rebuild the docker container

Running the tests with start.sh automatically runs S3Monitor. If for some reason you need to individually run the S3Monitor on terminal you can do :

```bash
aws-vault exec identity -- php S3Monitor.php
```

To run the tests in local environment

```bash
aws-vault exec identity -- docker run -it -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=localhost:7002" --network="host" --rm casperjs:latest ./start.sh 'tests/'
```

Note that in order for S3monitor to work locally, it is necessary to omit the -v volume option

The old way of doing this (left here for historical reasons) was:

```bash
docker run -d -e "CASPER_EMAIL_USER=CASPER_EMAIL_USER" -e "CASPER_EMAIL_PASSWORD=CASPER_EMAIL_PASSWORD" -e "BASE_DOMAIN=BASE_DOMAIN" --name casperjs casperjs:latest
docker exec casperjs ./start.sh 'tests/'
```

That old way, some tests will fail though, because S3Monitor only works via aws-vault, because S3Monitor needs to talk to real S3

## Running a subset of the tests

It is possible to run a subset of the tests by manually doing what the Makefile does.

From the root directory of the project, build the casperjs docker container:

```
docker build -f ./tests/Dockerfile  -t casperjs:latest .
```

Then specify the test suites you want to run on the command line:

```
aws-vault exec moj-lpa-dev -- time docker run -it -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=localhost:7002" --network="host" casperjs:latest ./start.sh tests/02-Signup/02-signup.js tests/02-Signup/03-activate.js tests/03-Authentication/ tests/05-CreatePfLpa/
```

You can specify individual files and/or directories. The tests run in the order specified. If you specify a directory, the tests will be run from that directory in name order before moving onto the next test file/directory.

As shown in the command above, you will typically need the following test suites to run first for the other test suites to subsequently work correctly:

```
tests/02-Signup/02-signup.js
tests/02-Signup/03-activate.js
tests/03-Authentication/
```

This is because the 04+ test suites assume that a valid login token is available on the test client and the above tests perform a sign up and login.

## Advanced information

When a test suite is run, a service is activated inside the container. The relevant test emails got to S3 and S3 bucket is monitored. This allows the test
suites to activate accounts and reset passwords. The email account needs to be setup for IMAP and should have auto-expunge set to Off.

It can be useful to start up a shell to debug failing tests.  To login to the docker container:

```bash
aws-vault exec identity -- docker run -it -v ${PWD}/tests:/mnt/test -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=<PUBLIC_FRONT_URL>" --net=host --rm casperjs:latest /usr/bin/env bash
```

Note that once inside anything you do is ephemeral; when you exit and re-enter the state of the container will have been reset.
