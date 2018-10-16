# MongoDB -> Postgres Migration

Should be run from within the API container.


## Users

To run user (+ profile) migrations: `/app/db/move/users/run.sh`

## Who Are You

To WhoAreYou user migrations: `/app/db/move/whoareyou/run.sh`

## Applications

To run LPA application migrations: `/app/db/move/applications/run.sh`

This also performs a data cleanup, only migration applications for which we have a current, active, user account.
