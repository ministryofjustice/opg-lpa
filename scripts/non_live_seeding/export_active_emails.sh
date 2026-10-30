#!/usr/bin/env sh
# Usage:
#   ./export_active_emails.sh [lines_per_file] [output_dir]
#
# Exports identity (email) of all active users to <output_dir>/active_emails.csv,
# then splits it into <output_dir>/active_emails_part_aa.csv, ..._ab.csv, etc.
# lines_per_file defaults to 100000, output_dir to the current directory.

set -e

LINES="${1:-100000}"
OUT_DIR="${2:-.}"
ALL_FILE="${OUT_DIR}/active_emails.csv"
SQL="COPY (SELECT identity FROM public.users WHERE active = true AND identity IS NOT NULL) TO STDOUT WITH (FORMAT csv)"

mkdir -p "$OUT_DIR"

# CloudShell: set DBInstanceIdentifier (and optionally DB_SECRET_ID) to derive the connection settings
if [ -n "$DBInstanceIdentifier" ]; then
    OPG_LPA_POSTGRES_HOSTNAME=$(aws rds describe-db-instances --db-instance-identifier "$DBInstanceIdentifier" | jq -r '.DBInstances[0].Endpoint.Address')
    OPG_LPA_POSTGRES_PASSWORD=$(aws secretsmanager get-secret-value --secret-id "${DB_SECRET_ID:-production/api_rds_credentials}" | jq -r '.SecretString | fromjson | .password')
    OPG_LPA_POSTGRES_USERNAME="${OPG_LPA_POSTGRES_USERNAME:-opglpaapi}"
    OPG_LPA_POSTGRES_NAME="${OPG_LPA_POSTGRES_NAME:-api2}"
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

split -l "$LINES" "$ALL_FILE" "${OUT_DIR}/active_emails_part_"
for f in "${OUT_DIR}"/active_emails_part_??; do mv "$f" "$f.csv"; done

echo "Exported $(wc -l < "$ALL_FILE") emails to ${ALL_FILE}, split into files of up to ${LINES} lines."
