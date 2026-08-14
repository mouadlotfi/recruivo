#!/bin/sh
set -eu

mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache

if [ ! -e public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

exec "$@"
