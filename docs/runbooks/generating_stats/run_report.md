# Generating Payment, LPA Type and totals Statistics

## Requirements

Follow the instructions in the [Using cloud9 to access RDS instance](../cloud9/db_access.md) document, to prepare the running of the scripts below.

## Run stats scripts

1. The following sql scripts contain statistics queries generaly asked for by the business:

   ``` bash
    ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_lpa_application_types.sql #application types
    ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_lpa_payment_types.sql     #payment types
    ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_totals.sql                #totals for LPA created, LPA started, LPA completed.
    ```

2. The scripts above have date ranges paramterized in. Change the date ranges in the files to suit your needs e.g.

    ``` sql
    -- edit this date range as needed
    \set datefrom '2019-11-23 00:00:00'
    \set dateto '2020-11-22 23:59:59'
    ```

3. Run the query files as needed against the `api2` database e.g.

    ``` bash
    psql api2 < ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_lpa_application_types.sql #application types
    psql api2 < ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_lpa_payment_types.sql     #payment types
    psql api2 < ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_totals.sql                #totals for LPA created, LPA started, LPA completed.

    ```
