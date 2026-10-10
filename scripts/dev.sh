#!/usr/bin/env bash
#
# Run the local Docker stack, in the normal or the demo configuration.
#
#   ./scripts/dev.sh up                     # build + start, wait for health
#   ./scripts/dev.sh seed                   # migrate --seed
#   ./scripts/dev.sh demo up                # same, with APP_IS_DEMO + demo database
#   ./scripts/dev.sh demo seed              # demo:reset --force (canonical dataset)
#   ./scripts/dev.sh artisan route:list     # artisan inside the app container
#   ./scripts/dev.sh demo artisan demo:reset --force
#   ./scripts/dev.sh logs | shell | restart | down
#
# Both modes are the same compose project, so only one runs at a time: the second
# `up` recreates app/queue/scheduler/migrate/vite with the other mode's
# environment. Volumes are shared and each mode owns its own database and Redis key
# space, so switching never destroys the other mode's data.
set -euo pipefail

cd "$(dirname "$0")/.."

MODE=normal
if [ "${1:-}" = demo ]; then
    MODE=demo
    shift
fi

COMPOSE_FILES=(-f docker-compose.yml -f docker-compose.dev.yml)
if [ "${MODE}" = demo ]; then
    COMPOSE_FILES+=(-f docker-compose.demo-dev.yml)
fi

compose() {
    docker compose "${COMPOSE_FILES[@]}" "$@"
}

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env from .env.example"
fi

# Probed from inside the container: it is the only thing that guarantees the
# request reached PHP, and it needs no host curl and no port arithmetic.
wait_for_health() {
    local attempt
    for attempt in $(seq 1 20); do
        if compose exec -T app curl -fsS -o /dev/null http://127.0.0.1/api/health >/dev/null 2>&1; then
            return 0
        fi
        sleep 3
    done

    echo "The app did not answer /api/health within 60s; recent logs:" >&2
    compose logs --tail=50 app migrate >&2 || true

    return 1
}

case "${1:-}" in
    up)
        # APP_KEY is empty in a fresh .env, and /api/health encrypts a string, so
        # the stack never reports healthy without it. Generate it before `up`:
        # compose injects env_file into a container once, at creation, so a key
        # written after the containers started would not reach them.
        if ! grep -qE '^APP_KEY=base64:' .env; then
            compose run --rm --no-deps --build app php artisan key:generate --force
        fi

        compose up -d --build
        wait_for_health

        app_port="$(sed -n 's/^APP_PORT=//p' .env | tail -1)"
        echo
        echo "App:     http://localhost:${app_port:-8000}"
        echo "Mailpit: http://localhost:8025"
        if [ "${MODE}" = demo ]; then
            echo "Mode:    demo - APP_IS_DEMO=true, database recruivo_demo_db"
            echo "Seed:    ./scripts/dev.sh demo seed"
        else
            echo "Mode:    normal"
            echo "Seed:    ./scripts/dev.sh seed"
        fi
        ;;
    down)
        compose down
        ;;
    logs)
        compose logs -f --tail=100 app queue scheduler vite
        ;;
    restart)
        # Restarts the long-running processes (queue worker, scheduler, PHP) without
        # recreating containers. Use `up` after an .env or compose change: those are
        # read at container creation, not at process start.
        compose restart
        wait_for_health
        ;;
    shell)
        compose exec app bash
        ;;
    seed)
        if [ "${MODE}" = demo ]; then
            compose exec -T app php artisan demo:reset --force
        else
            compose exec -T app php artisan migrate --seed
        fi
        ;;
    artisan)
        shift
        compose exec -T app php artisan "$@"
        ;;
    *)
        echo "usage: scripts/dev.sh [demo] {up|down|logs|shell|restart|seed|artisan <args...>}" >&2
        exit 1
        ;;
esac
