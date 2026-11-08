#!/usr/bin/env bash
# Restores a backup made by docker/backup.sh. This REPLACES the current database and PDFs.
# Usage: docker/restore.sh backups/20261108-020000
set -euo pipefail
cd "$(dirname "$0")/.."

source_dir="${1:?Pass the backup folder, e.g. backups/20261108-020000}"
compose="docker compose ${COMPOSE_FILES:-}"

read -r -p "Replace the current database and PDFs with ${source_dir}? [y/N] " answer
[[ "$answer" == "y" || "$answer" == "Y" ]] || { echo "Cancelled."; exit 1; }

gunzip -c "${source_dir}/database.sql.gz" \
    | $compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -u root "$MYSQL_DATABASE"'
rm -rf storage/app/private
tar -xzf "${source_dir}/storage-private.tar.gz" -C storage/app

echo "Restored from ${source_dir}"
