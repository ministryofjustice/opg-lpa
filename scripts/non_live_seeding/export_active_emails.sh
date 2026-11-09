#!/usr/bin/env sh
# Usage:
#   ./export_active_emails.sh [lines_per_file] [output_dir]
#
# Exports identity (email) of all active users to <output_dir>/active_emails.csv,
# then splits it into <output_dir>/active_emails_part_aa.csv, ..._ab.csv, etc., each starting with an "email address" header row.
# lines_per_file defaults to 100000, output_dir to the current directory.

set -e

LINES_PER_FILE="${1:-100000}"
OUT_DIR="${2:-.}"
ALL_FILE="${OUT_DIR}/active_emails.csv"
SQL="COPY (SELECT identity FROM public.users WHERE active = true AND identity IS NOT NULL) TO STDOUT WITH (FORMAT csv)"

mkdir -p "$OUT_DIR"

# CloudShell: set DB_SECRET_ID (e.g. <env>/api_rds_credentials) to take connection settings from the secret.
# Optionally set DBInstanceIdentifier to connect to a specific instance (e.g. a reader) instead of the secret's host.
if [ -n "$DB_SECRET_ID" ]; then
    SECRET=$(aws secretsmanager get-secret-value --secret-id "$DB_SECRET_ID" --query SecretString --output text)
    OPG_LPA_POSTGRES_HOSTNAME=$(echo "$SECRET" | jq -r .host)
    OPG_LPA_POSTGRES_PORT=$(echo "$SECRET" | jq -r .port)
    OPG_LPA_POSTGRES_USERNAME=$(echo "$SECRET" | jq -r .username)
    OPG_LPA_POSTGRES_PASSWORD=$(echo "$SECRET" | jq -r .password)
    OPG_LPA_POSTGRES_NAME="${OPG_LPA_POSTGRES_NAME:-api2}"
    if [ -n "$DBInstanceIdentifier" ]; then
        OPG_LPA_POSTGRES_HOSTNAME=$(aws rds describe-db-instances --db-instance-identifier "$DBInstanceIdentifier" | jq -r '.DBInstances[0].Endpoint.Address')
    fi
fi

if [ -n "$OPG_LPA_POSTGRES_HOSTNAME" ]; then
    PGPASSWORD=${OPG_LPA_POSTGRES_PASSWORD} PGOPTIONS='-c default_transaction_read_only=on' psql \
        --host="${OPG_LPA_POSTGRES_HOSTNAME}" \
        --port="${OPG_LPA_POSTGRES_PORT:-5432}" \
        --username="${OPG_LPA_POSTGRES_USERNAME}" \
        --dbname="${OPG_LPA_POSTGRES_NAME}" \
        -c "$SQL" > "$ALL_FILE"
else
    docker exec -i -e PGOPTIONS='-c default_transaction_read_only=on' lpa-postgres psql --username=lpauser --dbname=lpadb -c "$SQL" > "$ALL_FILE"
fi

split -l "$LINES_PER_FILE" "$ALL_FILE" "${OUT_DIR}/active_emails_part_"
for f in "${OUT_DIR}"/active_emails_part_??; do
    { echo "email address"; cat "$f"; } > "$f.csv"
    rm "$f"
done

echo "Exported $(wc -l < "$ALL_FILE") emails to ${ALL_FILE}, split into files of up to ${LINES_PER_FILE} lines."
