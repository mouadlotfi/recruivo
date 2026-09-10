#!/usr/bin/env bash
#
# Restore a backup produced by scripts/backup.sh.
#
#   APP_ENV_FILE=/mnt/hdd2-data/containers/recruivo/.env \
#       ./scripts/restore.sh --force ./backups/20260910T101500Z
#
# DESTRUCTIVE: the current database and uploaded files are replaced. Both
# artifacts are verified before anything is deleted.
#
# Environment:
#   COMPOSE_PROJECT_NAME  compose project to restore into (default: recruivo)
#   APP_ENV_FILE          deployment env file; also passed to compose as --env-file
set -euo pipefail

PROJECT_NAME="${COMPOSE_PROJECT_NAME:-recruivo}"

if [ "${1:-}" != "--force" ]; then
    echo "Usage: $0 --force <backup-directory>" >&2
    echo "Restoring replaces the current database and uploads; pass --force to proceed." >&2
    exit 1
fi

SOURCE="${2:-}"
if [ -z "${SOURCE}" ] || [ ! -d "${SOURCE}" ]; then
    echo "Backup directory '${SOURCE}' does not exist." >&2
    exit 1
fi

DATABASE_DUMP="${SOURCE}/database.sql.gz"
STORAGE_ARCHIVE="${SOURCE}/storage.tar.gz"

for artifact in "${DATABASE_DUMP}" "${STORAGE_ARCHIVE}"; do
    if [ ! -s "${artifact}" ]; then
        echo "Missing or empty artifact: ${artifact}" >&2
        exit 1
    fi
done

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
tar tzf "${STORAGE_ARCHIVE}" > /dev/null

echo "Restoring database (project '${PROJECT_NAME}')"
# Recreate the schema the way the mysql image created it - no explicit charset,
# so tables inherit the same server default as the original deployment instead
# of silently changing collation.
compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -e \
    "DROP DATABASE IF EXISTS \`$MYSQL_DATABASE\`; CREATE DATABASE \`$MYSQL_DATABASE\`"'
gunzip -c "${DATABASE_DUMP}" | compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql'

echo "Restoring uploaded files"
compose exec -T app rm -rf /var/www/html/storage/app
compose exec -T app tar xzf - -C /var/www/html/storage < "${STORAGE_ARCHIVE}"

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
