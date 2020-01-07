# LPA Online Service
The Office of the Public Guardian Lasting Power of Attorney online service: Managed by opg-org-infra &amp; Terraform.


## Local Development Setup

The first time you bring up the environment:

```
git clone git@github.com:ministryofjustice/opg-lpa.git
cd opg-lpa

docker-compose run front-composer
docker-compose run admin-composer
docker-compose run api-composer
docker-compose run pdf-composer

docker-compose up
```

You will also need a copy of the local config file `service-front/config/autoload/local.php`. Any developer on the team
should be able to provide you with this.


The LPA Tool service will be available via https://localhost:7002/home
The Admin service will be available via https://localhost:7003

The API service will be available (direct) via http://localhost:7001

After the first time, you bring up the environment with:
```
docker-compose up
```

### Tests

To run the unit tests
```bash
docker-compose run front-app /app/vendor/bin/phpunit
docker-compose run admin-app /app/vendor/bin/phpunit
docker-compose run api-app /app/vendor/bin/phpunit
docker-compose run pdf-app /app/vendor/bin/phpunit
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