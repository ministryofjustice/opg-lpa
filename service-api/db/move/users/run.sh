#!/bin/bash

echo "Script has already been run. Exiting."
exit 1

set -e

cd /app/db/move/users

echo "Exporting users from Mongo"
mongoexport --host=mongodb-01,mongodb-02,mongodb-03 --ssl --sslAllowInvalidCertificates --username opglpa-auth \
--password $OPG_LPA_AUTH_MONGODB_PASSWORD \
--db opglpa-auth --authenticationDatabase opglpa-auth --collection user --type csv \
--fields _id,identity,password_hash,activation_token,active,failed_login_attempts,created,last_updated,activated,last_login,last_failed_login,deletedAt,inactivity_flags,password_reset_token,email_update_request \
> users-dump.csv

echo "Exporting profiles from Mongo"
mongoexport --host=mongodb-01,mongodb-02,mongodb-03 --ssl --sslAllowInvalidCertificates --username opglpa-api \
--password $OPG_LPA_API_MONGODB_PASSWORD \
--db opglpa-api --authenticationDatabase opglpa-api --collection user --type csv \
--fields _id,name,address,dob,email \
> profiles-dump.csv

echo "Converting data Mongo -> Postgres"
php process-users.php > users-converted.csv

echo "Import the data into Postgres"
export PGPASSWORD=$OPG_LPA_POSTGRES_PASSWORD
psql --username=$OPG_LPA_POSTGRES_USERNAME --host=$OPG_LPA_POSTGRES_HOSTNAME --dbname=$OPG_LPA_POSTGRES_NAME --file=copy.sql

echo "Removing CSV files"
rm *.csv
