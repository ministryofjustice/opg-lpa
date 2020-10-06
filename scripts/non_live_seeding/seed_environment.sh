#!/usr/bin/env sh
# The following variables are set from docker-compose when run locally,
# from ECS container definitions when run as a task in AWS,
# or from scripts/non_live_seeding/.envrc when executed manually from a SSH terminal.
# OPG_LPA_STACK_NAME
# OPG_LPA_STACK_ENVIRONMENT
# OPG_LPA_POSTGRES_HOSTNAME
# OPG_LPA_POSTGRES_PORT
# OPG_LPA_POSTGRES_NAME
# OPG_LPA_POSTGRES_USERNAME
# OPG_LPA_POSTGRES_PASSWORD

AWS_DEFAULT_REGION=eu-west-1

check_db_exists()
{
if [ "$( PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} lpadb -tAc "SELECT 1 FROM pg_database WHERE datname='${OPG_LPA_POSTGRES_NAME}'" )" = '1' ]
then
    echo "LPA Database exists. Can continue"
else
    echo "LPA Database does not exist. Seeding will fail"
fi
}

#check_users_table_exists()
#{
#if [ "$( PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} lpadb -tAc "SELECT 1 FROM pg_database WHERE datname='${OPG_LPA_POSTGRES_NAME}'" )" = '1' ]
#then
    #echo "Users table exists. Can continue"
#else
    #echo "Users table does not exist. Seeding will fail"
#fi
#}

if [ "$OPG_LPA_STACK_NAME" == "production" ]; then
  echo "These scripts must not be run on production."
  exit 0
fi

echo "Waiting for postgres to be ready"
timeout 90s sh -c 'pgready=1; until [ ${pgready} -eq 0 ]; do pg_isready -h ${OPG_LPA_POSTGRES_HOSTNAME} -d ${OPG_LPA_POSTGRES_NAME}; pgready=$? ; sleep 5 ; done'

API_OPTS="--host=${OPG_LPA_POSTGRES_HOSTNAME} --username=${OPG_LPA_POSTGRES_USERNAME}"
echo "Waiting for database to be created"
check_db_exists

# PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
#   ${OPG_LPA_POSTGRES_NAME} \
#   -f clear_tables.sql

sleep 20

PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
  ${OPG_LPA_POSTGRES_NAME} \
  -f seed_test_users.sql

PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
  ${OPG_LPA_POSTGRES_NAME} \
  -f seed_test_applications.sql
