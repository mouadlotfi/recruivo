#!/usr/bin/env bash
#
# Deploy the Recruivo stack from GHCR. Shared by the CI production and demo jobs
# so their ~90% identical deploy logic lives in one place.
#
#   ./scripts/deploy.sh <project-name> <display-name> [--seed-demo]
#
#   ./scripts/deploy.sh recruivo Production
#   ./scripts/deploy.sh recruivo-demo Demo --seed-demo
#
# Environment (set by the workflow job):
#   APP_TAG       image tag to deploy, e.g. sha-<commit>
#   APP_PORT      published app port; the health probe uses only its port part
#   APP_ENV_FILE  deployment env file; also passed to compose as --env-file
#   APP_IMAGE     untagged image repository; pinned to APP_TAG below
set -euo pipefail

source "$(dirname "$0")/lib/compose.sh"

if [ "$#" -lt 2 ]; then
    echo "Usage: $0 <project-name> <display-name> [--seed-demo]" >&2
    exit 2
fi

PROJECT_NAME="$1"
DISPLAY_NAME="$2"
SEED_DEMO=false
if [ "${3:-}" = "--seed-demo" ]; then
    SEED_DEMO=true
fi

TARGET_TAG="${APP_TAG}"

# The job-level APP_IMAGE is an untagged repository, and being set it
# wins over the compose default that would append APP_TAG - every
# deploy silently pulled :latest and the rollback below could not pin
# an older build. Pin this build's reference explicitly instead.
APP_IMAGE="${APP_IMAGE}:${TARGET_TAG}"
export APP_IMAGE
HEALTH_TIMEOUT=120
MIGRATE_TIMEOUT=300
POLL_INTERVAL=3
HTTP_PORT="${APP_PORT##*:}"

# `env_file:` only injects into the container; compose interpolation
# (`${DB_HOST:-postgres}`, `${COMPOSE_PROFILES}`, ...) reads the shell or an
# `--env-file` - pass the deployment env file to both. lib/compose.sh has already
# put it in COMPOSE_ENV_ARGS; the guard below keeps a missing file fatal.
# Host-specific, so it is not a path in this repository: set the
# APP_ENV_FILE variable on this environment (production/demo).
if [ ! -f "${APP_ENV_FILE:-}" ]; then
    echo "::error::APP_ENV_FILE is not set, or is not a file - set the APP_ENV_FILE variable on this environment"
    exit 1
fi

PREV_CID=$(compose ps -q app 2>/dev/null || true)
PREV_TAG=""
if [ -n "${PREV_CID}" ]; then
    PREV_REF=$(docker inspect --format '{{.Config.Image}}' "${PREV_CID}" 2>/dev/null || true)
    PREV_TAG="${PREV_REF##*:}"
    # An untagged reference yields no tag: leave it empty to skip the rollback.
    if [ "${PREV_TAG}" = "${PREV_REF}" ]; then
        PREV_TAG=""
    fi
fi

echo "Pulling images for tag ${TARGET_TAG}..."
if ! APP_TAG="${TARGET_TAG}" compose pull; then
    echo "::error::Failed to pull images for tag ${TARGET_TAG}"
    exit 1
fi

echo "Updating containers and running migrations..."
APP_TAG="${TARGET_TAG}" compose up -d --remove-orphans

# `migrate` runs concurrently with `up -d`, so the app can answer the
# health probe before the schema is current: wait for its exit code.
echo "Waiting for migrations to complete..."
MIGRATE_CID=$(compose ps -a -q migrate 2>/dev/null || true)
MIGRATE_EXIT=1
if [ -n "${MIGRATE_CID}" ]; then
    # `timeout` (124) keeps a hung migration from stalling the job until the
    # workflow timeout, which would bypass the rollback below.
    MIGRATE_EXIT=$(timeout "${MIGRATE_TIMEOUT}" docker wait "${MIGRATE_CID}" || echo 124)
fi

MIGRATE_FAILED=false
if [ -z "${MIGRATE_CID}" ]; then
    MIGRATE_FAILED=true
    echo "::error::migrate service container not found; migrations were not applied"
elif [ "${MIGRATE_EXIT}" != "0" ]; then
    MIGRATE_FAILED=true
    echo "::error::Migration container exited with status ${MIGRATE_EXIT}; skipping health checks"
    compose logs --tail=50 migrate || true
fi

echo "Waiting for services to become healthy..."
START_TIME=$(date +%s)
DEPLOY_OK=false

while [ "${MIGRATE_FAILED}" = "false" ]; do
    ELAPSED=$(( $(date +%s) - START_TIME ))

    APP_CID=$(compose ps -q app 2>/dev/null || true)
    APP_HEALTH="starting"
    if [ -n "${APP_CID}" ]; then
        APP_HEALTH=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "${APP_CID}" 2>/dev/null || echo "error")
    fi

    echo "Status (${ELAPSED}s): app=${APP_HEALTH}"

    # Transiently unhealthy while migrations are applied: only the
    # overall timeout decides failure.

    if [ "${APP_HEALTH}" = "healthy" ]; then
        HTTP_STATUS=$(curl -fsS -o /dev/null -w "%{http_code}" --max-time 5 "http://127.0.0.1:${HTTP_PORT}/api/health" 2>/dev/null || true)
        if [ "${HTTP_STATUS}" = "200" ]; then
            echo "Smoke test passed (HTTP 200 from /api/health)"
            DEPLOY_OK=true
            break
        fi
    fi

    if [ "${ELAPSED}" -ge "${HEALTH_TIMEOUT}" ]; then
        echo "::error::Timed out after ${HEALTH_TIMEOUT}s waiting for containers to become healthy"
        break
    fi

    sleep "${POLL_INTERVAL}"
done

if [ "${DEPLOY_OK}" != "true" ]; then
    echo "::error::${DISPLAY_NAME} deployment failed; fetching recent container logs"
    compose logs --tail=50 || true

    if [ -n "${PREV_TAG}" ] && [ "${PREV_TAG}" != "${TARGET_TAG}" ]; then
        echo "::warning::Rolling back to previous tag: ${PREV_TAG}"
        APP_IMAGE="${APP_IMAGE%:*}:${PREV_TAG}" APP_TAG="${PREV_TAG}" compose up -d --remove-orphans || {
            echo "::error::Rollback to ${PREV_TAG} failed"
        }
    fi
    exit 1
fi

if [ "${SEED_DEMO}" = "true" ]; then
    echo "Seeding/resetting demo dataset..."
    compose exec -T app php artisan demo:reset --force

    docker image prune -f >/dev/null 2>&1 || true
fi

echo "${DISPLAY_NAME} deployment finished successfully."
