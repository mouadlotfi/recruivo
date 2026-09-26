#!/usr/bin/env bash
#
# Dump the Recruivo database and uploaded files into a timestamped directory.
#
#   APP_ENV_FILE=/path/to/containers/recruivo/.env ./scripts/backup.sh
#
# Environment:
#   COMPOSE_PROJECT_NAME  compose project to back up (default: recruivo)
#   APP_ENV_FILE          deployment env file; also passed to compose as
#                         --env-file so DB_HOST/REDIS_HOST resolve (see README)
#   BACKUP_DIR            where backups are written (default: <repo>/backups)
#   BACKUP_KEEP_DAYS      prune local backups older than this (default: 7, 0 keeps all)
#   BACKUP_STORAGE        back up uploaded files too (default: 1; 0 = database only)
#   BACKUP_REMOTE         optional rsync target, e.g. user@host:/srv/backups/recruivo
#
# Restore with scripts/restore.sh.
set -euo pipefail

PROJECT_NAME="${COMPOSE_PROJECT_NAME:-recruivo}"
BACKUP_DIR="${BACKUP_DIR:-$(cd "$(dirname "$0")/.." && pwd)/backups}"
BACKUP_KEEP_DAYS="${BACKUP_KEEP_DAYS:-7}"
BACKUP_STORAGE="${BACKUP_STORAGE:-1}"
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

# Database. PGPASSWORD keeps the password out of the container's process list.
echo "  - database (pgsql)"
compose exec -T postgres sh -c 'PGPASSWORD="$DB_PASSWORD" exec pg_dump \
    --username="$DB_USERNAME" --dbname="$DB_DATABASE" --no-owner --no-acl' \
    | gzip -9 > "${TARGET}/database.sql.gz"

# Candidate resumes, logos and private uploads; logs, caches and Redis (stale
# sessions, expendable queue) are deliberately left out. Skipped entirely with
# BACKUP_STORAGE=0, which makes the snapshot a database-only one (restore.sh
# then leaves the uploads on disk untouched).
if [ "${BACKUP_STORAGE}" = "1" ]; then
    echo "  - storage"
    compose exec -T app tar czf - -C /var/www/html/storage app > "${TARGET}/storage.tar.gz"
fi

# Never keep a truncated or empty artifact.
test -s "${TARGET}/database.sql.gz"
gzip -t "${TARGET}/database.sql.gz"
if [ "${BACKUP_STORAGE}" = "1" ]; then
    test -s "${TARGET}/storage.tar.gz"
    tar tzf "${TARGET}/storage.tar.gz" > /dev/null
fi
# Nor one that holds no schema at all: an empty dump still passes every check
# above, and the retention prune below would then delete the usable backups.
# awk reads to EOF, so `set -o pipefail` cannot see a SIGPIPE from an early exit.
zcat "${TARGET}/database.sql.gz" \
    | awk '/^(CREATE TABLE|COPY )/ { found = 1 } END { exit found ? 0 : 1 }' \
    || { echo "Database dump contains no tables - is the postgres service healthy?" >&2; exit 1; }
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
