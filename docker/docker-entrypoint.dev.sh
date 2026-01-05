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
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
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

clear_caches() {
    echo "Development mode - clearing caches..."
    php artisan config:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
}

create_storage_link() {
    if [ ! -L "public/storage" ]; then
        php artisan storage:link 2>/dev/null || true
    fi
}

install_dependencies() {
    if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
        echo "Installing Composer dependencies..."
        composer install --no-interaction --prefer-dist
    fi
    
    if [ ! -d "node_modules" ]; then
        echo "Installing npm dependencies..."
        npm ci
    fi
}

start_vite_dev_server() {
    if [ "${VITE_DEV:-true}" = "true" ]; then
        echo "Starting Vite dev server in background..."
        npm run dev -- --host 0.0.0.0 &
    fi
}

main() {
    echo "=== Laravel Development Docker Entrypoint ==="
    
    ensure_directories
    fix_permissions
    install_dependencies
    
    if [ -n "$DB_HOST" ]; then
        wait_for_service "$DB_HOST" "${DB_PORT:-3306}"
    fi
    
    if [ -n "$REDIS_HOST" ]; then
        wait_for_service "$REDIS_HOST" "${REDIS_PORT:-6379}"
    fi
    
    run_migrations
    clear_caches
    create_storage_link
    start_vite_dev_server
    
    echo "=== Starting Apache ==="
    exec "$@"
}

main "$@"
