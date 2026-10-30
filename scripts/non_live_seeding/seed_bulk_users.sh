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

# Refuse to seed production or preproduction (matches names like production/api_rds_credentials or api2-<env>-production-1)
refuse_if_live() {
    case $(printf '%s' "$1" | tr '[:upper:]' '[:lower:]') in
        *production*)
            echo "Refusing to seed: '$1' looks like a production or preproduction environment" >&2
            exit 1
            ;;
    esac
}
refuse_if_live "$DB_SECRET_ID"
refuse_if_live "$OPG_LPA_POSTGRES_HOSTNAME"

# CloudShell: set DB_SECRET_ID (e.g. <env>/api_rds_credentials) to take connection settings from the secret.
if [ -n "$DB_SECRET_ID" ]; then
    SECRET=$(aws secretsmanager get-secret-value --secret-id "$DB_SECRET_ID" --query SecretString --output text)
    OPG_LPA_POSTGRES_HOSTNAME=$(echo "$SECRET" | jq -r .host)
    OPG_LPA_POSTGRES_PORT=$(echo "$SECRET" | jq -r .port)
    OPG_LPA_POSTGRES_USERNAME=$(echo "$SECRET" | jq -r .username)
    OPG_LPA_POSTGRES_PASSWORD=$(echo "$SECRET" | jq -r .password)
    OPG_LPA_POSTGRES_NAME="${OPG_LPA_POSTGRES_NAME:-api2}"
fi
refuse_if_live "$OPG_LPA_POSTGRES_HOSTNAME"

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
