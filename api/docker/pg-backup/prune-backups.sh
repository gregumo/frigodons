#!/bin/bash
set -euo pipefail

BUCKET="s3://$BACKUP_S3_BUCKET/"
CUTOFF=$(date -d "2 months ago" +"%Y-%m-%d_%H-%M-%S")
IFS=',' read -ra DATABASES <<< "$DATABASE_NAMES"

S3CMD_ARGS=(
    --host="$BACKUP_S3_HOST"
    --host-bucket="%(bucket)s.$BACKUP_S3_HOST"
    --access_key="$BACKUP_S3_ACCESS_KEY"
    --secret_key="$BACKUP_S3_SECRET_KEY"
)

TMP_FILE=$(mktemp)
trap 'rm -f "$TMP_FILE"' EXIT

echo "Looking for backups older than ${CUTOFF%%_*}..."

for database in "${DATABASES[@]}"; do
    s3cmd ls "$BUCKET$database/" "${S3CMD_ARGS[@]}" |
    while read -r _ _ _ url; do
        filename="${url##*/}"

        if [[ "$filename" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}$ && "$filename" < "$CUTOFF" ]]; then
            printf "%s\t%s\n" "$filename" "$url"
        fi
    done > "$TMP_FILE"

    unset previous_day
    sort -r "$TMP_FILE" |
    while IFS=$'\t' read -r filename url; do
        day="${filename%%_*}"

        if [[ "${previous_day:-}" == "$day" ]]; then
            echo "DELETE: $url"
            s3cmd del "$url" "${S3CMD_ARGS[@]}"
        else
            echo "KEEP:   $url"
            previous_day="$day"
        fi
    done
done
