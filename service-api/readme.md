
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

## Connecting to the postgres server

The API app talks directly to the postgres back-end from PHP. Occasionally,
it can be useful to manually log in to the postgres server to inspect the
database and/or dump data from it. To do this:

```
docker exec -it lpa-api-app sh
apk add postgresql-client
psql -h postgres -U lpauser lpadb
Password: <enter lpapass>
```

You should now have a psql command line on the postgres server.

## Manually accessing the API

Example of how to get an authentication token for username + password in dev:

```
curl -i -L -X POST -H "Content-Type: application/json" -d '{"username": "seeded_test_user@digital.justice.gov.uk", "password": "Pass1234"}' "http://localhost:7001/v2/authenticate"
```

## License

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).
