# Generating Payment and LPA Type Statistics

## Requirements

Follow the instructions in the [Using cloud9 to access RDS instance](../cloud9/db_access.md) document, to prepare the running of the scripts below.

## Run stats scripts

 run the queries against the `api2` database.

``` bash
psql api2 < ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_lpa_application_types.sql
psql api2 < ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_lpa_payment_types.sql
```
