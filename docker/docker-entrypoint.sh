#!/bin/bash
set -e

readonly LARAVEL_DIRS=(
    "storage/app/public"
    "storage/framework/cache/data"
    "storage/framework/sessions"
    "storage/framework/views"
    "storage/framework/testing"
    "storage/logs"
    "bootstrap/cache"
)

ensure_directories() {
    for dir in "${LARAVEL_DIRS[@]}"; do
        mkdir -p "$dir"
    done
}

fix_permissions() {
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
}

wait_for_service() {
    local host="$1"
    local port="$2"
    local max_attempts="${3:-30}"
    local attempt=1

    echo "Waiting for $host:$port..."
    while ! nc -z "$host" "$port" 2>/dev/null; do
        if [ $attempt -ge $max_attempts ]; then
            echo "ERROR: $host:$port not available after $max_attempts attempts"
            return 1
        fi
        echo "Attempt $attempt/$max_attempts - waiting..."
        sleep 2
        ((attempt++))
    done
    echo "$host:$port is available"
}

run_migrations() {
    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        echo "Running database migrations..."
        php artisan migrate --force
    fi
}

optimize_laravel() {
    if [ "${APP_ENV:-production}" = "production" ]; then
        echo "Optimizing Laravel for production..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache
    else
        echo "Development mode - clearing caches..."
        php artisan config:clear || true
        php artisan cache:clear || true
        php artisan route:clear || true
        php artisan view:clear || true
    fi
}

create_storage_link() {
    if [ ! -L "public/storage" ]; then
        php artisan storage:link || true
    fi
}

main() {
    echo "=== Laravel Docker Entrypoint ==="
    echo "Environment: ${APP_ENV:-production}"
    
    ensure_directories
    fix_permissions
    
    if [ -n "$DB_HOST" ]; then
        wait_for_service "$DB_HOST" "${DB_PORT:-3306}"
    fi
    
    if [ -n "$REDIS_HOST" ]; then
        wait_for_service "$REDIS_HOST" "${REDIS_PORT:-6379}"
    fi
    
    run_migrations
    optimize_laravel
    create_storage_link
    
    echo "=== Starting Apache ==="
    exec "$@"
}

main "$@"
