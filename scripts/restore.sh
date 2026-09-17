#!/usr/bin/env bash
#
# Restore a backup produced by spatie/laravel-backup (`backup:run`, a .zip) or by
# scripts/backup.sh (a directory).
#
#   APP_ENV_FILE=/path/to/containers/recruivo/.env \
#       ./scripts/restore.sh --force ./backups/20260910T101500Z
#   APP_ENV_FILE=/path/to/containers/recruivo/.env \
#       ./scripts/restore.sh --force /mnt/hdd2-data/backups/recruivo/Recruivo/recruivo-2026-09-17-12-11-08.zip
#
# Note the <APP_NAME> directory: spatie nests every archive one level below the
# configured root.
#
# DESTRUCTIVE: the current database (and, when the snapshot has one, the uploaded
# files) are replaced. Both artifacts are verified before anything is deleted.
# A snapshot taken with BACKUP_STORAGE=0 holds only database.sql.gz; the uploads
# on disk are then left as they are.
#
# Environment:
#   COMPOSE_PROJECT_NAME  compose project to restore into (default: recruivo)
#   APP_ENV_FILE          deployment env file; passed to compose as --env-file and
#                         read for the archive password
#   RESTORE_WORK_DIR      where a .zip is extracted (default: the system temp
#                         directory). The archive's own directory is not usable:
#                         the app owns it, so the operator cannot write there.
set -euo pipefail

PROJECT_NAME="${COMPOSE_PROJECT_NAME:-recruivo}"

if [ "${1:-}" != "--force" ]; then
    echo "Usage: $0 --force <backup-directory|backup.zip>" >&2
    echo "Restoring replaces the current database and uploads; pass --force to proceed." >&2
    exit 1
fi

SOURCE="${2:-}"

COMPOSE_ENV_ARGS=()
if [ -n "${APP_ENV_FILE:-}" ] && [ -f "${APP_ENV_FILE}" ]; then
    COMPOSE_ENV_ARGS=(--env-file "${APP_ENV_FILE}")
fi

compose() {
    docker compose "${COMPOSE_ENV_ARGS[@]}" -p "${PROJECT_NAME}" "$@"
}

# Where the archive password comes from - the same source the app reads it from.
# APP_ENV_FILE is how the deploy host is documented to be restored; the repository
# .env is the local development equivalent.
ENV_FILE="${APP_ENV_FILE:-}"
if [ -z "${ENV_FILE}" ] && [ -f .env ]; then
    ENV_FILE=.env
fi

# The image the running app came from. Resolving it through compose would
# re-evaluate APP_IMAGE and APP_TAG, which only CI exports, and fall back to
# :latest - a tag that need not be the build running here, or even exist on this
# host. The running container already knows its own image.
app_image() {
    compose ps -q app | head -n 1 | xargs -r docker inspect --format '{{.Config.Image}}'
}

# spatie encrypts every entry with WinZip AES as soon as BACKUP_ARCHIVE_PASSWORD is
# set, and nothing on a stock host can read that format: Info-ZIP's unzip knows
# only the legacy ZipCrypto, and Python's zipfile rejects AES (method 99)
# outright. PHP's ZipArchive is backed by libzip - the same library that wrote the
# archive - so the extraction runs in the app image. Unencrypted archives go
# through the same path, so there is only one way in to keep correct.
extract_archive() {
    local archive="$1"
    local destination="$2"
    local password_file="${WORK_DIR}/archive-password.env"

    # Only the password has to travel. Handing docker the deployment env file
    # whole would mean it parsing a Laravel .env, which is not the format
    # --env-file expects, and the extraction never touches the database.
    : > "${password_file}"
    if [ -n "${ENV_FILE}" ] && [ -f "${ENV_FILE}" ]; then
        sed -n '/^BACKUP_ARCHIVE_PASSWORD=/p' "${ENV_FILE}" \
            | tail -n 1 \
            | sed 's/^BACKUP_ARCHIVE_PASSWORD="\(.*\)"$/BACKUP_ARCHIVE_PASSWORD=\1/' \
            >> "${password_file}"
    fi

    mkdir -p "${destination}"

    # --user 0 so the extraction can write into the temp directory the operator
    # created and owns privately; the modes are handed back below.
    docker run --rm --user 0 --entrypoint php \
        --env-file "${password_file}" \
        -e "RESTORE_ARCHIVE=$(basename "${archive}")" \
        -v "$(cd "$(dirname "${archive}")" && pwd)":/restore/src:ro \
        -v "${destination}":/restore/out \
        "$(app_image)" -r '
            $archive = "/restore/src/" . getenv("RESTORE_ARCHIVE");

            $zip = new ZipArchive;

            if ($zip->open($archive) !== true) {
                fwrite(STDERR, "error: cannot open {$archive}\n");
                exit(1);
            }

            $password = getenv("BACKUP_ARCHIVE_PASSWORD");

            if (is_string($password) && $password !== "") {
                $zip->setPassword($password);
            }

            if (! $zip->extractTo("/restore/out")) {
                fwrite(STDERR, "error: cannot extract {$archive} - was it written with this BACKUP_ARCHIVE_PASSWORD?\n");
                exit(1);
            }

            $zip->close();

            // The extraction runs as root, so everything it writes is owned by
            // root inside a directory the operator owns. Without handing the
            // modes back, the cleanup trap leaves the whole tree behind in the
            // temp directory, unremovable by the operator who created it.
            $entries = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator("/restore/out", FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($entries as $entry) {
                chmod($entry->getPathname(), $entry->isDir() ? 0777 : 0644);
            }
        '
}

# spatie/laravel-backup writes one zip holding db-dumps/<driver>-<database>.sql plus
# the uploaded files under their absolute container paths. Normalise it into the
# directory layout the rest of this script works with, so both formats restore the
# same way and there is only one restore path to keep correct.
WORK_DIR=""
if [ -n "${SOURCE}" ] && [ -f "${SOURCE}" ]; then
    WORK_DIR="$(mktemp -d "${RESTORE_WORK_DIR:-${TMPDIR:-/tmp}}/recruivo-restore.XXXXXX")"
    trap 'rm -rf "${WORK_DIR}"' EXIT

    echo "Extracting $(basename "${SOURCE}")"
    extract_archive "${SOURCE}" "${WORK_DIR}/extracted"

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
