#!/usr/bin/env sh
# Usage:
#   ./seed_bulk_lpas.sh <user_label> [count]
#
# count defaults to 8000 if not given.

set -e

USER_LABEL="$1"
COUNT="${2:-8000}"

if [ -z "$USER_LABEL" ]; then
    echo "Usage: $0 <user_label> [count]"
    exit 1
fi

USER_ID=$(printf '%s' "$USER_LABEL" | md5sum | cut -d' ' -f1)

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
SQL_FILE="${SCRIPT_DIR}/seed_bulk_lpas.sql"

if [ -n "$OPG_LPA_POSTGRES_HOSTNAME" ]; then
    echo "Seeding ${COUNT} LPAs for user ${USER_LABEL} (id ${USER_ID}) on ${OPG_LPA_POSTGRES_HOSTNAME}..."
    PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} psql \
        --host="${OPG_LPA_POSTGRES_HOSTNAME}" \
        --port="${OPG_LPA_POSTGRES_PORT:-5432}" \
        --username="${OPG_LPA_POSTGRES_USERNAME}" \
        --dbname="${OPG_LPA_POSTGRES_NAME}" \
        -v user_id="'${USER_ID}'" \
        -v user_label="'${USER_LABEL}'" \
        -v count="${COUNT}" \
        -f "${SQL_FILE}"
else
    echo "Seeding ${COUNT} LPAs for user ${USER_LABEL} (id ${USER_ID}) on local docker-compose postgres..."
    docker exec -i lpa-postgres psql \
        --username=lpauser \
        --dbname=lpadb \
        -v user_id="'${USER_ID}'" \
        -v user_label="'${USER_LABEL}'" \
        -v count="${COUNT}" \
        < "${SQL_FILE}"
fi

echo "Done. Sign in with ${USER_LABEL}@example.com and Pass1234."
