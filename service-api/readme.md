
# Lasting Power of Attorney API Service

The Lasting Power of Attorney API Service is responsible for managing and storing the details of LPAs made by users, and also for managing and storing users authentication details; plus the ability to authenticate against those details using either a email/password combination, or an authentication token. It is accessed via the Front End service.


## Creating a new database migration
```
php vendor/bin/phinx create MigrationName
```
Will create a new migration script template under `db/migrations/`

Documentation for filling out the template can be found here: https://book.cakephp.org/3.0/en/phinx.html

To start the migration
```
php vendor/bin/phinx migrate
```

To rollback
```
php vendor/bin/phinx rollback
```

## Running the integration tests for the API

We provide integration tests for the API. These touch the postgres server and depend on
data inserted by the seeding scripts (scripts/non_live_seeding/seed_environment.sh).

From the root directory of the project:

```
# start the local server stack
make dc-up

# run the functional tests against the API
make integration-api-local
```

### Quick and dirty way to run the functional tests

If you've got a local stack up and running, you can run the tests with:

```
php service-api/vendor/bin/phpunit service-api/tests/functional/
```

However, this depends on you figuring out how to install the service-api
composer dependencies first. You should also ensure you have the right version
of PHP installed locally (see service-api/composer.json).

## License

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).
