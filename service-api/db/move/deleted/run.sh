#!/bin/bash

set -e

cd /app/db/move/deleted

echo "Exporting data from Mongo"
mongoexport --host=mongodb-01,mongodb-02,mongodb-03 --ssl --sslAllowInvalidCertificates --username opglpa-auth \
--password $OPG_LPA_AUTH_MONGODB_PASSWORD \
--db opglpa-auth --authenticationDatabase opglpa-auth --collection log --type csv \
--fields identity_hash,type,reason,loggedAt \
> log-dump.csv

echo "Import the data into Postgres"
export PGPASSWORD=$OPG_LPA_POSTGRES_PASSWORD
psql --username=$OPG_LPA_POSTGRES_USERNAME --host=$OPG_LPA_POSTGRES_HOSTNAME --dbname=$OPG_LPA_POSTGRES_NAME --file=copy.sql

echo "Removing CSV files"
rm *.csv
