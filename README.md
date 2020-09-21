# LPA Online Service
The Office of the Public Guardian Lasting Power of Attorney online service: Managed by opg-org-infra &amp; Terraform.


## Local Development Setup

Intially, download the repo via:

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
