#!/usr/bin/env bash
#
# Restore a backup produced by spatie/laravel-backup (`backup:run`, a .zip) or by
# scripts/backup.sh (a directory).
#
#   APP_ENV_FILE=/path/to/containers/recruivo/.env \
#       ./scripts/restore.sh --force ./backups/20260910T101500Z
#   APP_ENV_FILE=/path/to/containers/recruivo/.env \
#       ./scripts/restore.sh --force /mnt/hdd2-data/backups/recruivo/recruivo-2026-09-16-10-59-06.zip
#
# DESTRUCTIVE: the current database (and, when the snapshot has one, the uploaded
# files) are replaced. Both artifacts are verified before anything is deleted.
# A snapshot taken with BACKUP_STORAGE=0 holds only database.sql.gz; the uploads
# on disk are then left as they are.
#
# Environment:
#   COMPOSE_PROJECT_NAME  compose project to restore into (default: recruivo)
#   APP_ENV_FILE          deployment env file; also passed to compose as --env-file
#   RESTORE_WORK_DIR      where a .zip is extracted (default: the system temp
#                         directory). The archive's own directory is not usable:
#                         the app owns it, so the operator cannot write there.
set -euo pipefail

PROJECT_NAME="${COMPOSE_PROJECT_NAME:-recruivo}"

if [ "${1:-}" != "--force" ]; then
    echo "Usage: $0 --force <backup-directory>" >&2
    echo "Restoring replaces the current database and uploads; pass --force to proceed." >&2
    exit 1
fi

SOURCE="${2:-}"

# spatie/laravel-backup writes one zip holding db-dumps/<driver>-<database>.sql plus
# the uploaded files under their absolute container paths. Normalise it into the
# directory layout the rest of this script works with, so both formats restore the
# same way and there is only one restore path to keep correct.
WORK_DIR=""
if [ -n "${SOURCE}" ] && [ -f "${SOURCE}" ]; then
    command -v unzip >/dev/null || { echo "unzip is required to read ${SOURCE}" >&2; exit 1; }

    WORK_DIR="$(mktemp -d "${RESTORE_WORK_DIR:-${TMPDIR:-/tmp}}/recruivo-restore.XXXXXX")"
    trap 'rm -rf "${WORK_DIR}"' EXIT

    echo "Extracting $(basename "${SOURCE}")"
    unzip -q "${SOURCE}" -d "${WORK_DIR}/extracted"

    DUMP_SQL="$(find "${WORK_DIR}/extracted/db-dumps" -maxdepth 1 -type f -name '*.sql' 2>/dev/null | head -n 1)"
    if [ -z "${DUMP_SQL}" ]; then
        echo "No database dump (db-dumps/*.sql) inside ${SOURCE}." >&2
        exit 1
    fi
    gzip -9 -c "${DUMP_SQL}" > "${WORK_DIR}/database.sql.gz"

    # Re-root the uploads at storage/ to match the tar the restore below expects.
    if [ -d "${WORK_DIR}/extracted/var/www/html/storage/app" ]; then
        tar czf "${WORK_DIR}/storage.tar.gz" -C "${WORK_DIR}/extracted/var/www/html/storage" app
    fi

    SOURCE="${WORK_DIR}"
fi

if [ -z "${SOURCE}" ] || [ ! -d "${SOURCE}" ]; then
    echo "Backup '${SOURCE}' does not exist." >&2
    exit 1
fi

DATABASE_DUMP="${SOURCE}/database.sql.gz"
STORAGE_ARCHIVE="${SOURCE}/storage.tar.gz"

if [ ! -s "${DATABASE_DUMP}" ]; then
    echo "Missing or empty database dump: ${DATABASE_DUMP}" >&2
    exit 1
fi

HAS_STORAGE=0
if [ -s "${STORAGE_ARCHIVE}" ]; then
    HAS_STORAGE=1
fi

COMPOSE_ENV_ARGS=()
if [ -n "${APP_ENV_FILE:-}" ] && [ -f "${APP_ENV_FILE}" ]; then
    COMPOSE_ENV_ARGS=(--env-file "${APP_ENV_FILE}")
fi

compose() {
    docker compose "${COMPOSE_ENV_ARGS[@]}" -p "${PROJECT_NAME}" "$@"
}

# Verify before destroying anything.
echo "Verifying ${SOURCE}"
gzip -t "${DATABASE_DUMP}"
if [ "${HAS_STORAGE}" = "1" ]; then
    tar tzf "${STORAGE_ARCHIVE}" > /dev/null
fi

echo "Restoring database (project '${PROJECT_NAME}')"
# The dump is plain SQL with no owner/ACL statements, so the recreate is all
# the preparation it needs. `psql` connects to the maintenance database to drop
# and recreate the target, which PostgreSQL refuses to do from a session on it.
# Separate `-c` flags: psql wraps a single -c holding more than one statement in
# a transaction, and DROP DATABASE cannot run there. PostgreSQL also refuses to
# drop a database other sessions are connected to (a request in flight, a queue
# worker, a concurrent pg_dump), so the first call connects *to the target* and
# evicts them: current_database() needs no SQL string literal, unlike the name,
# which cannot be passed through psql -c variables (psql does not interpolate
# them there). A target that does not exist yet has nothing to evict.
compose exec -T postgres sh -c 'PGPASSWORD="$DB_PASSWORD" exec psql \
    --username="$DB_USERNAME" --dbname="$DB_DATABASE" -v ON_ERROR_STOP=1 \
    -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = current_database() AND pid <> pg_backend_pid()"' \
    || echo "No sessions to evict (database '${DB_DATABASE:-?}' absent or unreachable)."
compose exec -T postgres sh -c 'PGPASSWORD="$DB_PASSWORD" exec psql \
    --username="$DB_USERNAME" --dbname=postgres -v ON_ERROR_STOP=1 \
    -c "DROP DATABASE IF EXISTS \"$DB_DATABASE\"" \
    -c "CREATE DATABASE \"$DB_DATABASE\""'
gunzip -c "${DATABASE_DUMP}" | compose exec -T postgres sh -c 'PGPASSWORD="$DB_PASSWORD" exec psql \
    --username="$DB_USERNAME" --dbname="$DB_DATABASE" -v ON_ERROR_STOP=1'

if [ "${HAS_STORAGE}" = "1" ]; then
    echo "Restoring uploaded files"
    compose exec -T app rm -rf /var/www/html/storage/app
    compose exec -T app tar xzf - -C /var/www/html/storage < "${STORAGE_ARCHIVE}"
else
    echo "No storage archive in this snapshot - uploaded files left untouched"
fi

echo "Applying any migrations newer than the backup"
compose exec -T app php artisan migrate --force --no-interaction

COMPOSE_HINT="docker compose -p ${PROJECT_NAME}"
if [ "${#COMPOSE_ENV_ARGS[@]}" -gt 0 ]; then
    COMPOSE_HINT="docker compose ${COMPOSE_ENV_ARGS[*]} -p ${PROJECT_NAME}"
fi

cat <<EOF

Restore complete. Restart the stack to drop stale caches and worker state:

    ${COMPOSE_HINT} restart app queue scheduler

Then verify: curl -fsS http://127.0.0.1:<port>/api/health
EOF
