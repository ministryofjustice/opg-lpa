# Generating Payment and LPA Type Statistics
Follow the instructions in `../cloud9/README.md` to configure the Cloud9 instance.
Then run the queries against the `api2` database.
```
psql api2 < ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_lpa_application_types.sql
psql api2 < ~/environment/opg-lpa/docs/runbooks/generating_stats/generating_stats_lpa_payment_types.sql
```