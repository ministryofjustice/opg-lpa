#!/usr/bin/env bash
# replace variables with env vars from the container
ACCOUNT_NAME=development
ENV_NAME=88-LPA3507ec2-online-lpa
AWS_DEFAULT_REGION=eu-west-1

if ${ENV_NAME:0:13} == "production" then
  echo "These scripts must not be run on production."
  # Terminate our shell script with success message
  exit 0
fi

API_DB_ENDPOINT=$(aws rds describe-db-instances --db-instance-identifier api-${ENV_NAME:0:13} | jq -r .'DBInstances'[0].'Endpoint'.'Address')
DB_PASSWORD=$(aws secretsmanager get-secret-value --secret-id ${ACCOUNT_NAME}/api_rds_password | jq -r .'SecretString')
DB_USERNAME=$(aws secretsmanager get-secret-value --secret-id ${ACCOUNT_NAME}/api_rds_username | jq -r .'SecretString')
API_OPTS="--host=${API_DB_ENDPOINT} --username=${DB_USERNAME}"

PGPASSWORD=${DB_PASSWORD} psql ${API_OPTS} \
  api2 \
  -f clear_tables.sql  

PGPASSWORD=${DB_PASSWORD} psql ${API_OPTS} \
  api2 \
  -f seed_test_users.sql

PGPASSWORD=${DB_PASSWORD} psql ${API_OPTS} \
  api2 \
  -f seed_test_applications.sql
