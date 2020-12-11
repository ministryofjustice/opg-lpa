
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

## Working with the API manually

When running under docker-compose, the API is on http://localhost:7001/

Requests should have the following headers set to mimic requests from the admin app (the main consumer of the API):

* Accept: application/json
* Content-Type: application/json
* User-Agent: LPA-ADMIN

To authenticate to the API:

* POST username, password to /v2/authenticate

For example:

```
$ curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "User-Agent: LPA-ADMIN" \
  -d '{"username": "seeded_test_user@digital.justice.gov.uk", "password": "Pass1234"}' \
  "http://localhost:7001/v2/authenticate"

{"userId":"082347fe0f7da026fa6187fc00b05c55","username":"seeded_test_user@digital.justice.gov.uk",
"last_login":"2020-01-21T15:16:02+0000","inactivityFlagsCleared":false,
"token":"yIU0G8NiTesl4hev0wXIQHpeipcdiAIiMvRpT0hZ2rl","expiresIn":4500,"expiresAt":"2020-12-03T12:16:34+0000"}
```

The `token` returned here can be sent with subsequent requests to other parts of the API:

```
$ curl -H "Accept: application/json" -H "User-Agent: LPA-ADMIN" \
  -H "Token: yIU0G8NiTesl4hev0wXIQHpeipcdiAIiMvRpT0hZ2rl" \
  "http://localhost:7001/v2/users/match?query=seeded_test_user"

{"userId":"082347fe0f7da026fa6187fc00b05c55","username":"seeded_test_user@digital.justice.gov.uk","isActive":true,
"lastLoginAt":{"date":"2020-12-03 11:01:34.000000","timezone_type":1,"timezone":"+00:00"},
"updatedAt":{"date":"2020-01-21 15:15:53.000000","timezone_type":1,"timezone":"+00:00"},
"createdAt":{"date":"2020-01-21 15:15:11.007119","timezone_type":1,"timezone":"+00:00"},
"activatedAt":{"date":"2020-01-21 15:15:53.000000",
"timezone_type":1,"timezone":"+00:00"},"lastFailedLoginAttemptAt":null,"failedLoginAttempts":0}
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
