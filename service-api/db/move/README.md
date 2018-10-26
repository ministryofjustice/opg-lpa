# MongoDB -> Postgres Migration

Should be run from within the API container.


## Applications

To run LPA application migrations: `/app/db/move/applications/run.sh`

This also performs a data cleanup, only migration applications for which we have a current, active, user account.

## Deleted Log

To deleted user log migrations: `/app/db/move/deleted/run.sh`



## Users

### Done and removed

To run user (+ profile) migrations: `/app/db/move/users/run.sh`

## Who Are You

### Done and removed

To WhoAreYou user migrations: `/app/db/move/whoareyou/run.sh`