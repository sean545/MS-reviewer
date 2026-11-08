#!/usr/bin/env bash
# Backs up the database and uploaded PDFs into backups/<timestamp>/. Keeps the newest 14 backups.
# Usage: docker/backup.sh            (add to the host's crontab for daily backups, e.g.
#        0 2 * * * cd /path/to/Reviewer && docker/backup.sh >> backups/backup.log 2>&1)
set -euo pipefail
cd "$(dirname "$0")/.."

compose="docker compose ${COMPOSE_FILES:-}"
stamp="$(date +%Y%m%d-%H%M%S)"
target="backups/${stamp}"
mkdir -p "$target"

$compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump --single-transaction --routines -u root "$MYSQL_DATABASE"' \
    | gzip > "${target}/database.sql.gz"
tar -czf "${target}/storage-private.tar.gz" -C storage/app private

echo "Backup written to ${target} ($(du -sh "$target" | cut -f1))"

ls -1dt backups/*/ 2>/dev/null | tail -n +15 | xargs -r rm -rf
