Functional tests for the API. These touch the postgres server and depend on
data inserted by the seeding scripts (scripts/non_live_seeding/seed_environment.sh).

# Running the tests

From the root directory of the project:

```
# start the local server stack
make dc-up

# run the functional tests against the API
make functional-api-local
```
