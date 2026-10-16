#!/bin/bash
set -euo pipefail

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
BUCKET="s3://$BACKUP_S3_BUCKET"
IFS=',' read -ra DATABASES <<< "$DATABASE_NAMES"

S3CMD_ARGS=(
    --host="$BACKUP_S3_HOST"
    --host-bucket="%(bucket)s.$BACKUP_S3_HOST"
    --access_key="$BACKUP_S3_ACCESS_KEY"
    --secret_key="$BACKUP_S3_SECRET_KEY"
)

backup_file=""
trap 'rm -f "$backup_file"' EXIT

for database in "${DATABASES[@]}"; do
    backup_file=$(mktemp "/tmp/${database}_${TIMESTAMP}.XXXXXX")

    echo "Starting backup for $database..."

    PGPASSWORD="$DATABASE_PASSWORD" pg_dump \
        --host="$DATABASE_HOST" \
        --port="$DATABASE_PORT" \
        --username="$DATABASE_USERNAME" \
        --dbname="$database" \
        --no-password \
        --format=custom \
        --file="$backup_file"

    s3cmd put "$backup_file" "$BUCKET/$database/$TIMESTAMP" "${S3CMD_ARGS[@]}"

    rm -f "$backup_file"
    backup_file=""

    echo "Backup for $database completed and uploaded to $BUCKET/$database/"
done
