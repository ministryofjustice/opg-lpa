#!/usr/bin/env sh
# The following variables are set from docker-compose when run locally,
# from ECS container definitions when run as a task in AWS,
# or from scripts/non_live_seeding/.envrc when executed manually from a SSH terminal.
# OPG_LPA_STACK_NAME
# OPG_LPA_STACK_ENVIRONMENT
# OPG_LPA_POSTGRES_HOSTNAME
# OPG_LPA_POSTGRES_PORT
# OPG_LPA_POSTGRES_NAME - database name
# OPG_LPA_POSTGRES_USERNAME
# OPG_LPA_POSTGRES_PASSWORD

AWS_DEFAULT_REGION=eu-west-1
API_OPTS="--host=${OPG_LPA_POSTGRES_HOSTNAME} --username=${OPG_LPA_POSTGRES_USERNAME}"

check_db_exists()
{
    ret_val=0
    tries=0
    database_ready=0

    sql="SELECT COUNT(1) FROM pg_database WHERE datname='${OPG_LPA_POSTGRES_NAME}'"

    # ten minutes (40*15)
    while [ $tries -lt 40 ] ; do
        tries=$(($tries+1))

        database_ready=$(PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} ${OPG_LPA_POSTGRES_NAME} -tAc "$sql")

        if [ "$database_ready" -eq "1" ] ; then
            echo "LPA Database exists. Can continue"
            break
        fi

        sleep 15
    done

    if [ ! "$database_ready" -eq "1" ] ; then
        echo "LPA Database does not exist. Seeding will fail"
        ret_val=1
    fi

    return $ret_val
}

# returns 0 if tables are ready, 1 otherwise
check_tables_exist()
{
    count_tables=0
    tries=0

    sql="SELECT COUNT(*) FROM (
          SELECT FROM pg_tables
          WHERE  schemaname = 'public'
          AND    tablename  = 'users' OR tablename = 'applications'
    ) AS tables;"

    # expect two tables
    while [[ "$count_tables" -ne "2" ]] ; do
         tries=$(($tries+1))

         count_tables=$(PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} ${OPG_LPA_POSTGRES_NAME} -tAc "$sql")

         # error codes mean there are no tables
         if [ "$?" -ne "0" ] ; then
             count_tables=0
         fi

         # ten minutes (40*15)
         if [ $tries -gt 40 ] ; then
            break
         fi

         sleep 15
    done

    ret_val=1
    if [ "$count_tables" -eq "2" ] ; then
        ret_val=0
    fi

    return $ret_val
}

if [ "$OPG_LPA_STACK_ENVIRONMENT" == "production" ]; then
    echo "These scripts must not be run on production."
    exit 1
fi

echo "Waiting for postgres to be ready"
timeout 600s sh -c 'pgready=1; until [ ${pgready} -eq 0 ]; do pg_isready -h ${OPG_LPA_POSTGRES_HOSTNAME} -d ${OPG_LPA_POSTGRES_NAME} -U ${OPG_LPA_POSTGRES_USERNAME}; pgready=$? ; sleep 5 ; done'

echo "Checking database exists"
check_db_exists
if [ "$?" -ne "0" ] ; then
    echo "ERROR: database does not exist"
    exit 1
fi

echo "Waiting for tables to be ready"
check_tables_exist
if [ "$?" -ne "0" ] ; then
    echo "ERROR: Seeding aborted; database tables not ready in a timely fashion"
    exit 1
fi

echo "==-------=="
echo "Truncating any existing Seed data"
echo "==-------=="
PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
  ${OPG_LPA_POSTGRES_NAME} \
  --echo-all -f clear_tables.sql

echo "==-------=="
echo "Seeding data: Users Table"
echo "==-------=="
PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
  ${OPG_LPA_POSTGRES_NAME} \
  --echo-all -f seed_test_users.sql

echo "==-------=="
echo "Seeding data: LPA Applications Table"
echo "==-------=="
PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
  ${OPG_LPA_POSTGRES_NAME} \
  --echo-all -f seed_test_applications.sql

echo "==-------=="
echo "Seeding data: Feedback Table"
echo "==-------=="
PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
  ${OPG_LPA_POSTGRES_NAME} \
  --echo-all -f seed_test_feedback.sql

echo "==-------=="
echo "Seeding data: User Deletion Log Table"
echo "==-------=="
PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
  ${OPG_LPA_POSTGRES_NAME} \
  --echo-all -f seed_test_deletion_log.sql
