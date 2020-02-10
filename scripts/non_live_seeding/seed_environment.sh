#!/usr/bin/env sh
# The following variables aree set from docker-compose when run locally,
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

if [ "$OPG_LPA_STACK_NAME" == "production" ]; then
  echo "These scripts must not be run on production."
  exit 0
fi

API_OPTS="--host=${OPG_LPA_POSTGRES_HOSTNAME} --username=${OPG_LPA_POSTGRES_USERNAME}"

# PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
#   ${OPG_LPA_POSTGRES_NAME} \
#   -f clear_tables.sql

PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
  ${OPG_LPA_POSTGRES_NAME} \
  -f seed_test_users.sql

PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql ${API_OPTS} \
  ${OPG_LPA_POSTGRES_NAME} \
  -f seed_test_applications.sql
