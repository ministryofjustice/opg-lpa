# LPA Online Service
The Office of the Public Guardian Lasting Power of Attorney online service: Managed by opg-org-infra &amp; Terraform.


## Pre-requisites for Local Development

Set up software on your machine required to run the application locally:

*   Install `make` using the native package manager (assuming you are on Mac or Linux)
*   Install [docker](https://docs.docker.com/get-docker/)
*   Install [docker-compose](https://docs.docker.com/compose/install/)
*   Install `awscli`: while this can be done via brew, this failed for me on Linux, so I used [these instructions](https://docs.aws.amazon.com/cli/latest/userguide/install-cliv2.html) instead.
*   Install [homebrew](https://docs.brew.sh/)
*   Install dependencies for the Makefile using brew: `brew install aws-vault jq`

### Access to Amazon secrets

[Set up access to Amazon with MFA]().

Add a default profile which references your account to `~/.aws/config`:

```
[default]
region = eu-west-1
mfa_serial=arn:aws:iam::111111111111:mfa/your.name
```

The value for `mfa_serial` is visible in the AWS console under *My security credentials* after you've configured MFA.

Add a `moj-lpa-dev` profile to `~/.aws/config` which references the default profile; this should include the ARN of the dev operator role from AWS (you'll need webops to supply this):

```
[profile moj-lpa-dev]
role_arn=arn:aws:iam::111111111111:role/operator
source_profile = default
```

For the next step, you will need a temporary access key. Generate this using the AWS console, under *My security credentials*.

Add your default profile to aws-vault:

```
aws-vault add default
```

When prompted, enter the temporary access key you just generated via the AWS console. (You may also be prompted to create a new keyring on your machine using whatever method is natively available.)

Once this is done, check that you have access by running this command:

```
aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_email_sendgrid_api_key
```

You will be prompted for an MFA token, which should be displayed on whichever device you used to set up MFA for your Amazon account.

NB This command is run when starting the application locally, which is why you need to get this set up.

## Running the Application Locally

Download the repo via:

```
git clone git@github.com:ministryofjustice/opg-lpa.git
cd opg-lpa
```

Within `opg-lpa` directory to *run* the project for the first time use the following:

```
make dc-run
make
```

The `Makefile` will fetch secrets using `aws secretsmanager` and `docker-compose` commands together to pass along environment variables removing the need for local configuration files.


The LPA Tool service will be available via https://localhost:7002/home
The Admin service will be available via https://localhost:7003

The API service will be available (direct) via http://localhost:7001

After the first time, you can *run* the project by:
```
make
```

### Tests

To run the unit tests
```bash
make dc-unit-tests
```

For how to run the functional tests, please see seperate README in tests/functional directory

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
