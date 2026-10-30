#!/usr/bin/env sh
# Usage:
#   ./seed_bulk_users.sh [count] [prefix]
#
# Creates <count> active users (default 100) with emails <prefix>-<n>@example.com
# (prefix defaults to "bulk"), all with password Pass1234. Re-running skips existing users.

set -e

COUNT="${1:-100}"
PREFIX="${2:-bulk}"

SQL="INSERT INTO public.users (id, identity, password_hash, active, created, updated, activated)
SELECT md5('${PREFIX}-' || g), '${PREFIX}-' || g || '@example.com',
       '\$2y\$10\$C9QCpqBK/9xP7x04nUemhO.OvRc.AWCHOb/N0w8Z2SxOMfSnoNIMO',
       true, now(), now(), now()
FROM generate_series(1, ${COUNT}) g
ON CONFLICT DO NOTHING;"

# CloudShell: set DBInstanceIdentifier and DB_SECRET_ID (e.g. <env>/api_rds_credentials) to derive the connection settings
if [ -n "$DBInstanceIdentifier" ]; then
    if [ -z "$DB_SECRET_ID" ]; then
        echo "DB_SECRET_ID must be set when using DBInstanceIdentifier, e.g. export DB_SECRET_ID=<env>/api_rds_credentials"
        exit 1
    fi
    OPG_LPA_POSTGRES_HOSTNAME=$(aws rds describe-db-instances --db-instance-identifier "$DBInstanceIdentifier" | jq -r '.DBInstances[0].Endpoint.Address')
    OPG_LPA_POSTGRES_PASSWORD=$(aws secretsmanager get-secret-value --secret-id "$DB_SECRET_ID" | jq -r '.SecretString | fromjson | .password')
    OPG_LPA_POSTGRES_USERNAME="${OPG_LPA_POSTGRES_USERNAME:-opglpaapi}"
    OPG_LPA_POSTGRES_NAME="${OPG_LPA_POSTGRES_NAME:-api2}"
fi

if [ -n "$OPG_LPA_POSTGRES_HOSTNAME" ]; then
    PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql \
        --host="${OPG_LPA_POSTGRES_HOSTNAME}" \
        --port="${OPG_LPA_POSTGRES_PORT:-5432}" \
        --username="${OPG_LPA_POSTGRES_USERNAME}" \
        --dbname="${OPG_LPA_POSTGRES_NAME}" \
        -c "$SQL"
else
    docker exec -i lpa-postgres psql --username=lpauser --dbname=lpadb -c "$SQL"
fi

echo "Done. Sign in with ${PREFIX}-<1..${COUNT}>@example.com and Pass1234."
