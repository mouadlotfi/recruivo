#!/usr/bin/env bash
# =============================================================================
# Recruivo — Local CI/CD Rehearsal & Verification Script
# =============================================================================
# Reproduces the full GitHub Actions CI/CD pipeline locally before pushing:
#   1. Backend validation: Code style (Pint) & Tests (PHPUnit)
#   2. Frontend validation: TypeScript check (vue-tsc) & Vite production build
#   3. Docker image builds: Multi-stage targets ('production' & 'nginx')
#   4. Docker Compose validation: Configuration validation for Prod & Demo
#   5. Production stack rehearsal: Isolated container startup, migrations, health check
#   6. Demo stack rehearsal: Isolated container startup, seeding, health check
#   7. Automated teardown and cleanup of temporary test resources
# =============================================================================

set -euo pipefail

# ANSI color codes
BOLD='\033[1m'
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${CYAN}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_stage() {
    echo -e "\n${BOLD}${BLUE}===============================================================================${NC}"
    echo -e "${BOLD}${BLUE}  STAGE: $1${NC}"
    echo -e "${BOLD}${BLUE}===============================================================================${NC}\n"
}

# Base directories and identifiers
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${REPO_ROOT}"

GIT_SHA="$(git rev-parse --short=8 HEAD 2>/dev/null || echo "localdev")"
CI_RUN_ID="ci_$(date +%s)_$RANDOM"

PROD_PORT=8910
DEMO_PORT=8911

PROD_PROJECT="recruivo-ci-prod-${CI_RUN_ID}"
DEMO_PROJECT="recruivo-ci-demo-${CI_RUN_ID}"

PROD_MYSQL_VOLUME="recruivo_ci_prod_mysql_${CI_RUN_ID}"
PROD_REDIS_VOLUME="recruivo_ci_prod_redis_${CI_RUN_ID}"
PROD_STORAGE_VOLUME="recruivo_ci_prod_storage_${CI_RUN_ID}"

DEMO_MYSQL_VOLUME="recruivo_ci_demo_mysql_${CI_RUN_ID}"
DEMO_REDIS_VOLUME="recruivo_ci_demo_redis_${CI_RUN_ID}"
DEMO_STORAGE_VOLUME="recruivo_ci_demo_storage_${CI_RUN_ID}"

PROD_ENV_FILE="/tmp/${PROD_PROJECT}.env"
DEMO_ENV_FILE="/tmp/${DEMO_PROJECT}.env"
CI_OVERLAY="/tmp/${PROD_PROJECT}-overlay.yml"

APP_TAG="sha-${GIT_SHA}"
APP_IMAGE="ghcr.io/mouadlotfi/recruivo:${APP_TAG}"
NGINX_IMAGE="ghcr.io/mouadlotfi/recruivo-nginx:${APP_TAG}"

export APP_TAG
export APP_IMAGE
export NGINX_IMAGE

# =============================================================================
# Cleanup Trap Handler
# =============================================================================
cleanup() {
    local exit_code=$?
    log_info "Running cleanup handler for temporary CI resources..."

    # Tear down production rehearsal stack if started
    if [ -f "${PROD_ENV_FILE}" ]; then
        docker compose --env-file "${PROD_ENV_FILE}" --project-name "${PROD_PROJECT}" --profile production -f compose.yml -f compose.prod.yml -f "${CI_OVERLAY}" down -v --remove-orphans >/dev/null 2>&1 || true
        rm -f "${PROD_ENV_FILE}"
    fi

    # Tear down demo rehearsal stack if started
    if [ -f "${DEMO_ENV_FILE}" ]; then
        docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml -f "${CI_OVERLAY}" down -v --remove-orphans >/dev/null 2>&1 || true
        rm -f "${DEMO_ENV_FILE}"
    fi

    rm -f "${CI_OVERLAY}"

    # Remove temporary volumes if any linger
    docker volume rm "${PROD_MYSQL_VOLUME}" "${PROD_REDIS_VOLUME}" "${PROD_STORAGE_VOLUME}" "${DEMO_MYSQL_VOLUME}" "${DEMO_REDIS_VOLUME}" "${DEMO_STORAGE_VOLUME}" >/dev/null 2>&1 || true

    if [ ${exit_code} -eq 0 ]; then
        echo -e "\n${BOLD}${GREEN}===============================================================================${NC}"
        echo -e "${BOLD}${GREEN}  LOCAL CI REHEARSAL: ALL CHECKS PASSED! Ready for deployment.                ${NC}"
        echo -e "${BOLD}${GREEN}===============================================================================${NC}\n"
    else
        echo -e "\n${BOLD}${RED}===============================================================================${NC}"
        echo -e "${BOLD}${RED}  LOCAL CI REHEARSAL: FAILED (Exit Code: ${exit_code})                          ${NC}"
        echo -e "${BOLD}${RED}===============================================================================${NC}\n"
    fi

    exit ${exit_code}
}

trap cleanup EXIT INT TERM

# =============================================================================
# STAGE 1: Backend Checks (Pint + PHPUnit)
# =============================================================================
log_stage "1. Backend Quality & Test Suite"

log_info "Checking PHP code formatting with Laravel Pint..."
docker compose -f compose.yml -f compose.dev.yml run --rm app vendor/bin/pint --test
log_success "Pint code style check passed."

log_info "Executing PHPUnit test suite in isolated test environment..."
docker compose -f compose.yml -f compose.dev.yml run --rm app php artisan test --compact
log_success "PHPUnit test suite passed."

# =============================================================================
# STAGE 2: Frontend Checks (TypeScript + Vite Build)
# =============================================================================
log_stage "2. Frontend Quality & Bundle Compilation"

log_info "Running TypeScript typecheck (vue-tsc)..."
bun run typecheck
log_success "TypeScript check passed (0 errors)."

log_info "Building production client bundle (vite build)..."
bun run build
log_success "Vite client bundle compiled successfully."

# =============================================================================
# STAGE 3: Multi-Stage Docker Image Builds
# =============================================================================
log_stage "3. Multi-Stage Docker Image Builds"

log_info "Building PHP-FPM application image (target: production) -> ${APP_IMAGE}..."
docker build --target production -t "${APP_IMAGE}" -t "ghcr.io/mouadlotfi/recruivo:latest" .
log_success "Application image built: ${APP_IMAGE}"

log_info "Building Nginx proxy image (target: nginx) -> ${NGINX_IMAGE}..."
docker build --target nginx -t "${NGINX_IMAGE}" -t "ghcr.io/mouadlotfi/recruivo-nginx:latest" .
log_success "Nginx proxy image built: ${NGINX_IMAGE}"

# =============================================================================
# STAGE 4: Docker Compose Configuration Validation
# =============================================================================
log_stage "4. Docker Compose Configuration Validation"

log_info "Preparing isolated CI environment files..."

cat <<EOF > "${PROD_ENV_FILE}"
APP_NAME="Recruivo (CI-Production)"
APP_ENV=production
APP_KEY=base64:7f9E2k8nZ3vB5cX7mA9pQ1wE3rT5yU7iO9pA1sD3fG5=
APP_DEBUG=false
APP_IS_DEMO=false
APP_URL=http://localhost:${PROD_PORT}
APP_PORT=${PROD_PORT}
LOG_CHANNEL=stack
LOG_LEVEL=error
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=recruivo_db
DB_USERNAME=recruivo
DB_PASSWORD=ci_secret
DB_ROOT_PASSWORD=ci_secret
MYSQL_VOLUME_NAME=${PROD_MYSQL_VOLUME}
CACHE_DRIVER=redis
FILESYSTEM_DISK=public
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
MAIL_MAILER=array
SANCTUM_STATEFUL_DOMAINS=localhost:${PROD_PORT}
FRONTEND_URLS=http://localhost:${PROD_PORT}
QUEUE_FAILED_DRIVER=database-uuids
APP_ENV_FILE=${PROD_ENV_FILE}
APP_IMAGE=${APP_IMAGE}
NGINX_IMAGE=${NGINX_IMAGE}
APP_TAG=${APP_TAG}
EOF

cat <<EOF > "${DEMO_ENV_FILE}"
APP_NAME="Recruivo (CI-Demo)"
APP_ENV=demo
APP_PORT=${DEMO_PORT}
DEMO_APP_PORT=${DEMO_PORT}
LOG_CHANNEL=stack
LOG_LEVEL=info
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=recruivo_demo_db
DB_USERNAME=recruivo_demo
DB_PASSWORD=demo_secret
DB_ROOT_PASSWORD=demo_secret
DEMO_MYSQL_VOLUME_NAME=${DEMO_MYSQL_VOLUME}
DEMO_REDIS_VOLUME_NAME=${DEMO_REDIS_VOLUME}
DEMO_STORAGE_VOLUME_NAME=${DEMO_STORAGE_VOLUME}
CACHE_DRIVER=redis
FILESYSTEM_DISK=public
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
MAIL_MAILER=log
MAIL_FROM_ADDRESS="demo@recruivo.work"
MAIL_FROM_NAME="Recruivo Demo"
DEMO_SCHEDULED_RESET=true
SANCTUM_STATEFUL_DOMAINS=localhost:${DEMO_PORT}
FRONTEND_URLS=http://localhost:${DEMO_PORT}
QUEUE_FAILED_DRIVER=database-uuids
APP_ENV_FILE=${DEMO_ENV_FILE}
APP_IMAGE=${APP_IMAGE}
NGINX_IMAGE=${NGINX_IMAGE}
APP_TAG=${APP_TAG}
EOF

# Fast in-memory overlay for disposable CI MySQL containers (0 disk I/O latency)
cat <<EOF > "${CI_OVERLAY}"
services:
  mysql:
    volumes: !override []
    tmpfs:
      - /var/lib/mysql
    command: ["mysqld", "--innodb-flush-log-at-trx-commit=2", "--sync-binlog=0", "--innodb-doublewrite=0", "--innodb-use-native-aio=0", "--skip-log-bin"]
EOF
# Pre-create the external MySQL volume required by production compose specification
docker volume create "${PROD_MYSQL_VOLUME}" >/dev/null

log_info "Validating Production Compose configuration..."
docker compose --env-file "${PROD_ENV_FILE}" --project-name "${PROD_PROJECT}" --profile production -f compose.yml -f compose.prod.yml config >/dev/null
log_success "Production Compose configuration is valid."

log_info "Validating Demo Compose configuration..."
docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml config >/dev/null
log_success "Demo Compose configuration is valid."

# =============================================================================
# STAGE 5: Production Stack Rehearsal & Verification
# =============================================================================
log_stage "5. Production Stack Deployment Rehearsal"

log_info "Starting Production data services (MySQL & Redis)..."
docker compose --env-file "${PROD_ENV_FILE}" --project-name "${PROD_PROJECT}" --profile production -f compose.yml -f compose.prod.yml -f "${CI_OVERLAY}" up -d mysql redis

log_info "Waiting for Production database to become healthy..."
docker compose --env-file "${PROD_ENV_FILE}" --project-name "${PROD_PROJECT}" --profile production -f compose.yml -f compose.prod.yml -f "${CI_OVERLAY}" exec -T mysql sh -c '
    while ! mysqladmin ping -h 127.0.0.1 -u root -p"$MYSQL_ROOT_PASSWORD" --silent; do
        sleep 1
    done
'
log_success "Production database is healthy."

log_info "Executing deliberate production migrations (php artisan migrate --force)..."
docker compose --env-file "${PROD_ENV_FILE}" --project-name "${PROD_PROJECT}" --profile production -f compose.yml -f compose.prod.yml -f "${CI_OVERLAY}" run --rm --no-deps app php artisan migrate --force
log_success "Production migrations executed successfully."

log_info "Starting Production application services (App, Nginx, Queue, Scheduler)..."
docker compose --env-file "${PROD_ENV_FILE}" --project-name "${PROD_PROJECT}" --profile production -f compose.yml -f compose.prod.yml -f "${CI_OVERLAY}" up -d

log_info "Verifying Production health check endpoint (http://127.0.0.1:${PROD_PORT}/api/health)..."
for i in {1..30}; do
    if HEALTH_RESP=$(curl -s "http://127.0.0.1:${PROD_PORT}/api/health" 2>/dev/null); then
        STATUS=$(echo "${HEALTH_RESP}" | jq -r '.status // empty' 2>/dev/null || true)
        if [ "${STATUS}" = "healthy" ]; then
            log_success "Production health check verified: ${HEALTH_RESP}"
            break
        fi
    fi
    if [ $i -eq 30 ]; then
        log_error "Production health check timed out. Last response: ${HEALTH_RESP:-none}"
        docker compose --env-file "${PROD_ENV_FILE}" --project-name "${PROD_PROJECT}" --profile production -f compose.yml -f compose.prod.yml -f "${CI_OVERLAY}" logs --tail=50
        exit 1
    fi
    sleep 1
done

log_info "Verifying Production public homepage..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:${PROD_PORT}/")
if [ "${HTTP_CODE}" -eq 200 ] || [ "${HTTP_CODE}" -eq 302 ]; then
    log_success "Production web endpoint responded with HTTP ${HTTP_CODE}."
else
    log_error "Production web endpoint returned unexpected status: ${HTTP_CODE}"
    exit 1
fi

log_info "Verifying Production is not automatically seeded..."
USER_COUNT=$(docker compose --env-file "${PROD_ENV_FILE}" --project-name "${PROD_PROJECT}" --profile production -f compose.yml -f compose.prod.yml -f "${CI_OVERLAY}" exec -T mysql mysql -uroot -pci_secret -D recruivo_db -sNe 'SELECT COUNT(*) FROM users;' | tr -d '\r\n')
if [ "${USER_COUNT}" = "0" ]; then
    log_success "Production database verified clean (User count: 0)."
else
    log_error "Production database contains seeded data (User count: ${USER_COUNT})!"
    exit 1
fi

# =============================================================================
# STAGE 6: Demo Stack Rehearsal & Verification
# =============================================================================
log_stage "6. Demo Stack Deployment & Canonical Dataset Rehearsal"

log_info "Starting Demo data services..."
docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml -f "${CI_OVERLAY}" up -d mysql redis

log_info "Waiting for Demo database to become healthy..."
docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml -f "${CI_OVERLAY}" exec -T mysql sh -c '
    while ! mysqladmin ping -h 127.0.0.1 -u root -p"$MYSQL_ROOT_PASSWORD" --silent; do
        sleep 1
    done
'
log_success "Demo database is healthy."

log_info "Executing canonical demo migrations and seeding (php artisan migrate:fresh --seed --force)..."
docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml -f "${CI_OVERLAY}" run --rm --no-deps app php artisan migrate:fresh --seed --force
log_success "Demo database seeded successfully."

log_info "Starting Demo application services (App, Nginx, Queue, Scheduler)..."
docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml -f "${CI_OVERLAY}" up -d

log_info "Verifying Demo health check endpoint (http://127.0.0.1:${DEMO_PORT}/api/health)..."
for i in {1..30}; do
    if DEMO_HEALTH_RESP=$(curl -s "http://127.0.0.1:${DEMO_PORT}/api/health" 2>/dev/null); then
        DEMO_STATUS=$(echo "${DEMO_HEALTH_RESP}" | jq -r '.status // empty' 2>/dev/null || true)
        if [ "${DEMO_STATUS}" = "healthy" ]; then
            log_success "Demo health check verified: ${DEMO_HEALTH_RESP}"
            break
        fi
    fi
    if [ $i -eq 30 ]; then
        log_error "Demo health check timed out. Last response: ${DEMO_HEALTH_RESP:-none}"
        docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml -f "${CI_OVERLAY}" logs --tail=50
        exit 1
    fi
    sleep 1
done

log_info "Verifying Demo canonical dataset is active..."
DEMO_COMPANIES=$(docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml -f "${CI_OVERLAY}" exec -T mysql mysql -uroot -pdemo_secret -D recruivo_demo_db -sNe 'SELECT COUNT(*) FROM companies;' | tr -d '\r\n')
DEMO_JOBS=$(docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml -f "${CI_OVERLAY}" exec -T mysql mysql -uroot -pdemo_secret -D recruivo_demo_db -sNe 'SELECT COUNT(*) FROM jobs;' | tr -d '\r\n')
DEMO_USERS=$(docker compose --env-file "${DEMO_ENV_FILE}" --project-name "${DEMO_PROJECT}" --profile demo -f compose.yml -f compose.demo.yml -f "${CI_OVERLAY}" exec -T mysql mysql -uroot -pdemo_secret -D recruivo_demo_db -sNe 'SELECT COUNT(*) FROM users;' | tr -d '\r\n')

log_info "Demo Dataset metrics -> Companies: ${DEMO_COMPANIES}, Jobs: ${DEMO_JOBS}, Users: ${DEMO_USERS}"

if [ "${DEMO_COMPANIES}" -eq 15 ] && [ "${DEMO_JOBS}" -gt 50 ] && [ "${DEMO_USERS}" -gt 50 ]; then
    log_success "Demo canonical dataset verified intact and isolated."
else
    log_error "Demo dataset metrics mismatch! Expected 15 companies, >50 jobs, >50 users."
    exit 1
fi
