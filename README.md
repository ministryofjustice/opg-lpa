# LPA Online Service

The Office of the Public Guardian Lasting Power of Attorney online service: Managed by opg-org-infra &amp; Terraform.

## Pre-requisites for Local Development

Set up software on your machine required to run the application locally:

* Install `make` using the native package manager (assuming you are on Mac or Linux)
* Install [docker](https://docs.docker.com/get-docker/)
* Install [docker-compose](https://docs.docker.com/compose/install/)
* Install [homebrew](https://docs.brew.sh/) (mac only)

### clone repo

Download the repo via:

```bash
git clone https://github.com/ministryofjustice/opg-lpa.git
cd opg-lpa
```

### install pre-commit hooks (mac)

Install the precommit hooks as follows in the root of the repo directory (Mac only):

```bash
brew install php-code-sniffer
brew install php-cs-fixer
brew install phpstan
pre-commit install
```

This will install the pre-commit hooks for the repo. This covers:

* PHP code fixers
* Terraform
* Secrets commit detection (AWS, general secrets)
* Whitespace and end of file fixers

Add more as needed to the `.pre-commit-config.yaml`.

For linux users, revert to the instructions for installing phpcs, phpstan and precommit hooks:

* <https://github.com/squizlabs/PHP_CodeSniffer>
* <https://phpstan.org/user-guide/getting-started>
* <https://pre-commit.com/index.html#install>

### Running locally without 3rd party integrations

**This is the recommended approach for developers outside the Ministry of Justice.**

If you intend to run the application in tandem with 3rd party integrations, you currently require a Ministry of Justice AWS account. If you don't have one of these, you can still run the stack locally, minus these integrations, with:

```
make dc-up-out
```

The LPA Tool service will be available via <https://localhost:7002/home>

The Admin service will be available via <https://localhost:7003>

The API service will be available (direct) via <http://localhost:7001>

Note that running in this mode (currently) breaks the following integrations:

* When signing up, completing an LPA, changing email address etc., you won't receive any notification emails. This makes it impossible to sign up a new user as you won't get the confirmation link. Instead, you can use the test user to access the service: username: seeded_test_user@digital.justice.gov.uk / password: Pass1234
* You won't be able to make any payments for LPA applications. To avoid this, you can select the £0 charge LPA in testing, which will turn off prompts for payment.
* Postcode lookups for donor/attorney/certificate provider etc. will not work. You can work around this by selecting the manual address entry method.
* The script designed to periodically clean up inactive users, `service-api/module/Application/src/Command/AccountCleanupCommand.php`, will run, but will not email the admin address with the command output.

The long-term plan is for these integrations to be mocked out locally so that a more representative stack can be run in local development, without the need for AWS secrets (see below).

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
```

The value for `mfa_serial` is visible in the AWS console under *My security credentials* after you've configured MFA.

Add a `moj-lpa-dev` profile to `~/.aws/config` which references the default profile; this should include the ARN of the dev operator role from AWS (you'll need webops to supply this):

```ini
[profile moj-lpa-dev]
role_arn=arn:aws:iam::111111111111:role/operator
source_profile = default
```

For the next step, you will need a temporary access key. Generate this using the AWS console, under *My security credentials*.

Add your default profile to aws-vault:

```bash
aws-vault add default
```

When prompted, enter the temporary access key you just generated via the AWS console. (You may also be prompted to create a new keyring on your machine using whatever method is natively available.)

Once this is done, check that you have access by running this command:

```bash
aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_email_sendgrid_api_key
```

You will be prompted for an MFA token, which should be displayed on whichever device you used to set up MFA for your Amazon account.

NB This command is run when starting the application locally, which is why you need to get this set up.

## Running the application locally with integrations

Once you have access to Amazon secrets, you can run the application with integrations from the `opg-lpa` directory with:

```bash
make dc-up
```

In this mode, the `Makefile` will fetch secrets using `aws secretsmanager` and `docker-compose` commands, removing the need for local configuration files. Most of the sign up, email, postcode lookup and payment functionality should now work against dev variants of 3rd party systems.

### Tests

To run the unit tests

```bash
make dc-unit-tests
```

For how to run the functional tests, please see seperate README in tests/functional directory

### Cypress functional tests

The cypress functional tests can be run with:

```bash
make cypress-local
```

Once you've run this command, it can be useful to start the cypress
container and run the tests from a shell inside it. That way, you don't need
to re-build the whole container for each test run. You can also mount your
local test directory as a volume in the container so that you can quickly
modify and re-run tests. To get a command-line in the cypress container, do:

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

### Run the Cypress GUI via npm

the recommended way to run tests for the GUI runner is using npm. see: <https://docs.cypress.io/guides/getting-started/installing-cypress/#System-requirements>. this is useful if you want to see what the tests are physically doing.

**note**: the below assumes that the dev stack has been already started using `make dc-up`.

Install cypress globally:

```bash
npm i -g cypress
```

The package.json in the root of the repo has all of the required dev dependancies for Cypress. Add plugins to this as needed using

```bash
npm i <package-name> --saveDev
```

there are 2 options available:

For general use and exploratory work you can just run:

```bash
cypress open
```

Alternatively, the makefile has a default command to point at the existing cypress tests, and will list them. run the following:

```bash
make cypress-gui-npm
```

You should see a window appear with the features in it. This has been tested on Mac only so "Your Mileage May Vary" :tm:.

### Run Cypress GUI via XQuartz

To run the cypress tests in a GUI on Mac using XQuartz (X Windows), you need to install and start xquartz and socat :

```bash
brew install xquartz socat
socat TCP-LISTEN:6000,reuseaddr,fork UNIX-CLIENT:\"$DISPLAY\"
open -a Xquartz
```

If all is working, you will see XQuartz (X Window manager) running, and be able to start up a terminal.

Then run cypress :

```bash
make cypress-gui-local
```

You will see the Cypress GUI, which starts a browser (Chrome and Firefox are supported) to run tests.
You can edit tests and Cypress will (usually, although its not 100% perfect at spotting updates) re-run the
tests automatically as result.

### Updating composer dependencies

Composer install is run when the app containers are built, and on a standard `docker-compose up`.

It can also be run independently with:

```bash
docker-compose run <service>-composer
```

New packages can be added with:

```bash
docker-compose run <service>-composer composer require author/package
```

Packages can be removed with:

```bash
docker-compose run <service>-composer composer remove author/package
```
