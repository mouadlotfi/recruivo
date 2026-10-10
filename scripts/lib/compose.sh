#!/usr/bin/env bash
#
# Shared compose preamble for scripts/backup.sh, scripts/restore.sh and
# scripts/deploy.sh. Source it, do not execute it:
#
#   source "$(dirname "$0")/lib/compose.sh"
#
# Environment:
#   COMPOSE_PROJECT_NAME  compose project to act on (default: recruivo)
#   APP_ENV_FILE          deployment env file; also passed to compose as
#                         --env-file so DB_HOST/REDIS_HOST resolve (see README)

PROJECT_NAME="${COMPOSE_PROJECT_NAME:-recruivo}"

COMPOSE_ENV_ARGS=()
if [ -n "${APP_ENV_FILE:-}" ] && [ -f "${APP_ENV_FILE}" ]; then
    COMPOSE_ENV_ARGS=(--env-file "${APP_ENV_FILE}")
fi

compose() {
    docker compose "${COMPOSE_ENV_ARGS[@]}" -p "${PROJECT_NAME}" "$@"
}
