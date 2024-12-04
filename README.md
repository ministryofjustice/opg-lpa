# LPA Online Service    

The Office of the Public Guardian Lasting Power of Attorney online service: Managed by opg-org-infra &amp; Terraform.

[![repo standards badge](https://img.shields.io/badge/dynamic/json?color%3Dblue&style=for-the-badge&logo=github&label=MoJ+Compliant&query=%24.result&url=https://operations-engineering-reports.cloud-platform.service.justice.gov.uk/api/v1/compliant_public_repositories/opg-lpa
)](https://operations-engineering-reports.cloud-platform.service.justice.gov.uk/public-github-repositories.html#opg-lpa "Link to report")

## Pre-requisites for Local Development

Set up software on your machine required to run the application locally:

* Install `git`
* Install `make`
* Install [docker](https://docs.docker.com/get-docker/)
* Install [docker-compose](https://docs.docker.com/compose/install/)
* Install [homebrew](https://docs.brew.sh/) (Mac only)
* Install python3

### Clone repo

Download the repo via:

```bash
git clone https://github.com/ministryofjustice/opg-lpa.git
cd opg-lpa
```

### Install pre-commit hooks (Mac)

Install the precommit hooks and dependencies in the root of the repo directory:

```bash
brew install golang

# python code linting
brew install black

# javascript code linting
brew install eslint

# PHP code linting
brew install php-code-sniffer
brew install php-cs-fixer

brew install pre-commit

pre-commit install
```

Pre-commit hooks run any time you add a commit. They cover:

* PHP code formatting and fixing
* Python code linting
* JavaScript code linting
* Terraform
* Secrets commit detection (AWS, general secrets)
* Whitespace and end of file fixers

Add more as needed to the `.pre-commit-config.yaml`.

### Access to Amazon secrets

To run the application with 3rd party integrations, set up the software needed to support a Ministry of Justice AWS account:

* Install `awscli`: while this can be done via Homebrew, [these instructions](https://docs.aws.amazon.com/cli/latest/userguide/install-cliv2.html) may be more useful.

* Install dependencies for the Makefile using brew: `brew install aws-vault jq`

Set up access to Amazon with MFA.

Add a default profile which references your account to `~/.aws/config`:

```ini
[default]
region = eu-west-1
mfa_serial=arn:aws:iam::111111111111:mfa/your.name

[profile identity]
source_profile=''
```

The value for `mfa_serial` is visible in the AWS console under *My security credentials* after you've configured MFA.

Add a `moj-lpa-dev` profile to `~/.aws/config` which references the default profile; this should include the ARN of the dev operator role from AWS (you'll need webops to supply this):

```ini
[profile moj-lpa-dev]
role_arn=arn:aws:iam::111111111111:role/operator
source_profile=identity
```

For the next step, you will need a temporary access key. Generate this using the AWS console, under *My security credentials*.

Add your default profile to aws-vault:

```bash
aws-vault add identity
```

When prompted, enter the temporary access key you just generated via the AWS console. (You may also be prompted to create a new keyring on your machine using whatever method is natively available.)

Once this is done, check that you have access by running this command:

```bash
aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_gov_pay_key
```

You will be prompted for an MFA token, which should be displayed on whichever device you used to set up MFA for your Amazon account.

NB This command is run when starting the application locally, which is why you need to get this set up.

## Running the application locally

Once you have access to Amazon secrets, you can run the application from the `opg-lpa` directory with:

```bash
make dc-up
```

In this mode, the `Makefile` will fetch secrets using `aws secretsmanager` and `docker-compose` commands, removing the need for local configuration files. Most of the sign up, email, postcode lookup and payment functionality should now work against dev variants of 3rd party systems.

The LPA Tool service will be available via <https://localhost:7002/home>

The Admin service will be available via <https://localhost:7003>

The API service will be available (direct) via <http://localhost:7001>

### Tests

To run the unit tests for the PHP applications:

```bash
make dc-unit-tests
```

Unit tests for the individual components can be run from their individual directories, e.g.

```
cd service-front
/usr/local/opt/php@8.1/bin/php ./vendor/bin/phpunit
```

NB `shared`, `service-front`, `service-api` and `service-pdf` run using PHP 8.1, while
`service-admin` uses PHP 8.2. It's important to use the correct PHP version when running
the unit tests manually, as shown in the example above. Homebrew on mac allows you to install
different PHP versions in parallel, e.g.

```
brew install php@8.2
```

For instructions on how to run the functional tests, please see separate README in tests/functional directory.

### Load tests

The load tests are located in `tests/load`. They are written using [locust](https://locust.io/).

To run the load tests:

1. Start the stack (see above).
1. Create a virtualenv: `virtualenv -p python3 ~/.loadtestsvenv` (substitute your preferred
path for the virtual environment).
1. Install dependencies:
```bash
cd tests/load
pip install -e .
```
1. Run the test suite: `run_load_tests.sh tests/suite.py`
    The tests run indefinitely until you interrupt them. Reports are written to `build/load_tests`.
    Running `run_load_tests.sh` without arguments shows the available switches.

When working on the tests, it can be useful to debug HTTP requests made by the requests library.
To enable this, edit the `tests/load/load-test-config.json` file and set `"requests_debugging": true`.
The output is very verbose but can be useful for a low-level view of the HTTP layer.

### Cypress functional tests

**Note:** the below assumes that the dev stack has been already started using `make dc-up`.

Install python3. This is used to run the S3 monitor, which picks up activation emails (see below). On a mac:

```bash
brew install python3
```

Install the dependencies required by the S3 monitor:

```bash
pip3 install boto3
```

The cypress functional test suite can now be run with:

```bash
make cypress-local
```

NB this installs the nodejs dependencies required by cypress using npm.

You can open the test suite in the GUI and run individual tests with:

```bash
make cypress-open
```

This is usually the best way to work on and run individual tests.

It can occasionally be useful to start the cypress container and run the tests from
a shell inside it. That way, you don't need to re-build the whole container
for each test run. You can also mount your local test directory as a volume
in the container so that you can quickly modify and re-run tests. To get
a command-line in the cypress container, do:

```bash
make cypress-local-shell
```

This will give you a command prompt inside the container, from where you can
run the tests:

```bash
./cypress/start.sh
```

You can then modify the tests in your usual editor and re-run the modified tests
with the same command without having to rebuild/restart the container.

The package.json in the root of the repo has all of the required dev dependancies for Cypress. Add plugins to this as needed using

```bash
npm i <package-name> --saveDev
```

### The S3 monitor

The cypress test suite runs an instance of the S3 monitor. This is a Python application which polls an S3 bucket looking for emails sent during test runs.

All emails used during test runs, such as the addresses used for new user accounts, are in the lpa.opg.service.justice.gov.uk domain. This enables us to do the following:

1. During a test run, we send requests to the real Notify service, which then sends out emails on our behalf. Examples are account activation and password reset emails. These emails pass through standard email infrastructure; however, the email server for the lpa.opg.service.justice.gov.uk domain is rigged so that any emails sent to it end up in an S3 bucket (opg-lpa-casper-mailbox). (NB "casper" crops up for historical reasons, as the tests were previously implemented in casper.)
2. The S3 monitor polls the S3 bucket, copying any messages it finds into a local directory. The filename includes the ID of the test account.
3. Automated tests poll the local directory, looking for text files with specific IDs in their names (put there by the S3 monitor). The ID is included as part of the email address used to sign up or login a user, such as caspertests+1680708521545893617@lpa.opg.service.justice.gov.uk.
4. On finding the correct file (once it's been pulled down from S3), the email it contains is parsed for the appropriate links. For example, while testing sign up, the cypress tests look for a link to activate the newly-created account; once found, the link is followed to mimic a user opening the link from their email client, activating the account.

## Updating composer dependencies

Composer installs PHP dependencies when the app containers are built, and on a standard `make dc-up`.

However, if you upgrade a package in composer.json for one or more services, you'll need to update the corresponding lock file(s).

This can be done with:

```bash
docker run -v `pwd`/service-front/:/app/ composer update --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs
```

(replacing `service-front` with the path to the application component you are adding a package to; note that you'll need to do this for the following commands as well)

Packages can be added with:

```bash
docker run -v `pwd`/service-front/:/app/ composer require author/package --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs
```

Packages can be removed with:

```bash
docker run -v `pwd`/service-front/:/app/ composer remove author/package --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs
```
