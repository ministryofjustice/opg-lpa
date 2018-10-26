#!/bin/bash

echo "Script has already been run. Exiting."
exit 1

set -e

cd /app/db/move/whoareyou

echo "Exporting data from Mongo"
mongoexport --host=mongodb-01,mongodb-02,mongodb-03 --ssl --sslAllowInvalidCertificates --username opglpa-api \
--password $OPG_LPA_API_MONGODB_PASSWORD \
--db opglpa-api --authenticationDatabase opglpa-api --collection whoAreYou --type csv \
--fields _id,who,qualifier > whoareyou-dump.csv

echo "Converting data Mongo -> Postgres"
php process-whoareyou.php > whoareyou-converted.csv

echo "Import the data into Postgres"
export PGPASSWORD=$OPG_LPA_POSTGRES_PASSWORD
psql --username=$OPG_LPA_POSTGRES_USERNAME --host=$OPG_LPA_POSTGRES_HOSTNAME --dbname=$OPG_LPA_POSTGRES_NAME --file=copy.sql

echo "Removing CSV files"
rm *.csv
