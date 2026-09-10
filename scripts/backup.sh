#!/usr/bin/env bash
#
# Dump the Recruivo database and uploaded files into a timestamped directory.
#
#   APP_ENV_FILE=/mnt/hdd2-data/containers/recruivo/.env ./scripts/backup.sh
#
# Environment:
#   COMPOSE_PROJECT_NAME  compose project to back up (default: recruivo)
#   APP_ENV_FILE          deployment env file; also passed to compose as
#                         --env-file so DB_HOST/REDIS_HOST resolve (see README)
#   BACKUP_DIR            where backups are written (default: <repo>/backups)
#   BACKUP_KEEP_DAYS      prune local backups older than this (default: 7, 0 keeps all)
#   BACKUP_REMOTE         optional rsync target, e.g. user@host:/srv/backups/recruivo
#
# Restore with scripts/restore.sh.
set -euo pipefail

PROJECT_NAME="${COMPOSE_PROJECT_NAME:-recruivo}"
BACKUP_DIR="${BACKUP_DIR:-$(cd "$(dirname "$0")/.." && pwd)/backups}"
BACKUP_KEEP_DAYS="${BACKUP_KEEP_DAYS:-7}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"

COMPOSE_ENV_ARGS=()
if [ -n "${APP_ENV_FILE:-}" ] && [ -f "${APP_ENV_FILE}" ]; then
    COMPOSE_ENV_ARGS=(--env-file "${APP_ENV_FILE}")
fi

compose() {
    docker compose "${COMPOSE_ENV_ARGS[@]}" -p "${PROJECT_NAME}" "$@"
}

TARGET="${BACKUP_DIR}/${STAMP}"
mkdir -p "${TARGET}"
echo "Backing up project '${PROJECT_NAME}' into ${TARGET}"

# Database. MYSQL_PWD keeps the password out of the container's process list.
echo "  - database"
compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump \
    --single-transaction --routines --triggers --events --default-character-set=utf8mb4 \
    --databases "$MYSQL_DATABASE"' | gzip -9 > "${TARGET}/database.sql.gz"

# Candidate resumes, logos and private uploads. Logs and framework caches are
# regenerable and are deliberately left out. Redis (cache/session/queue) is not
# backed up: losing a queued notification is preferable to restoring stale state.
echo "  - storage"
compose exec -T app tar czf - -C /var/www/html/storage app > "${TARGET}/storage.tar.gz"

# Never keep a truncated or empty artifact.
test -s "${TARGET}/database.sql.gz"
test -s "${TARGET}/storage.tar.gz"
gzip -t "${TARGET}/database.sql.gz"
tar tzf "${TARGET}/storage.tar.gz" > /dev/null
echo "  - verified ($(du -sh "${TARGET}" | cut -f1))"

if [ "${BACKUP_KEEP_DAYS}" -gt 0 ]; then
    find "${BACKUP_DIR}" -mindepth 1 -maxdepth 1 -type d -mtime "+${BACKUP_KEEP_DAYS}" -print -exec rm -rf {} +
fi

if [ -n "${BACKUP_REMOTE:-}" ]; then
    command -v rsync > /dev/null || { echo "rsync is required for BACKUP_REMOTE" >&2; exit 1; }
    # Send the snapshot directory itself so rsync creates <remote>/<stamp>
    # (the remote root must already exist).
    rsync -az -- "${TARGET}" "${BACKUP_REMOTE%/}/"
    echo "  - copied to ${BACKUP_REMOTE%/}/${STAMP}"
else
    echo "WARNING: BACKUP_REMOTE is not set - this backup exists only on this host's disk." >&2
    echo "         Set BACKUP_REMOTE (rsync target) so a disk failure is survivable." >&2
fi

echo "Backup complete: ${TARGET}"
