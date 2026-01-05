#!/bin/bash
set -e

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
readonly MAX_RETRIES=30
readonly RETRY_INTERVAL=2

log_info() { echo -e "\033[0;34m[INFO]\033[0m $1"; }
log_success() { echo -e "\033[0;32m[OK]\033[0m $1"; }
log_warning() { echo -e "\033[0;33m[WARN]\033[0m $1"; }
log_error() { echo -e "\033[0;31m[ERROR]\033[0m $1"; }

check_docker() {
    if ! docker info > /dev/null 2>&1; then
        log_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
    log_success "Docker is running"
}

check_env_file() {
    if [ ! -f "$PROJECT_DIR/.env" ]; then
        log_warning "No .env file found"
        if [ -f "$PROJECT_DIR/.env.docker.example" ]; then
            log_info "Copying .env.docker.example to .env"
            cp "$PROJECT_DIR/.env.docker.example" "$PROJECT_DIR/.env"
            log_success "Created .env from template"
        else
            log_error "No .env.docker.example found. Please create a .env file."
            exit 1
        fi
    else
        log_success ".env file exists"
    fi
}

wait_for_container() {
    local container="$1"
    local health_cmd="$2"
    local attempt=1
    
    log_info "Waiting for $container to be healthy..."
    
    while [ $attempt -le $MAX_RETRIES ]; do
        if docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T "$container" $health_cmd > /dev/null 2>&1; then
            log_success "$container is healthy"
            return 0
        fi
        echo -n "."
        sleep $RETRY_INTERVAL
        ((attempt++))
    done
    
    echo ""
    log_error "$container failed to become healthy after $MAX_RETRIES attempts"
    return 1
}

run_in_container() {
    local description="$1"
    shift
    
    log_info "$description..."
    if docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T laravel "$@"; then
        log_success "$description completed"
    else
        log_error "$description failed"
        return 1
    fi
}

build_and_start() {
    log_info "Building and starting development containers..."
    docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
    log_success "Containers started"
}

generate_app_key() {
    local current_key
    current_key=$(grep "^APP_KEY=" "$PROJECT_DIR/.env" | cut -d '=' -f2)
    
    if [ -z "$current_key" ]; then
        log_info "Generating application key..."
        local new_key
        new_key=$(docker run --rm php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32));")
        sed -i "s|^APP_KEY=.*|APP_KEY=$new_key|" "$PROJECT_DIR/.env"
        log_success "Application key generated"
    else
        log_info "Application key already exists, skipping generation"
    fi
}

run_setup() {
    local run_migrations="${1:-true}"
    local run_seeders="${2:-false}"
    local fresh_install="${3:-false}"
    
    if [ "$run_migrations" = "true" ]; then
        if [ "$fresh_install" = "true" ]; then
            run_in_container "Dropping tables and running fresh migrations with seeders" php artisan migrate:fresh --seed --force
        elif [ "$run_seeders" = "true" ]; then
            run_in_container "Running migrations with seeders" php artisan migrate --seed --force
        else
            run_in_container "Running migrations" php artisan migrate --force
        fi
    fi
    
    run_in_container "Creating storage link" php artisan storage:link || true
}

show_help() {
    echo "Recruivo Development Docker Setup Script"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --fresh          Fresh install with migrations and seeders"
    echo "  --no-migrate     Skip database migrations"
    echo "  --seed           Run database seeders"
    echo "  --build-only     Only build containers, don't run setup"
    echo "  --down           Stop and remove containers"
    echo "  --logs           Show container logs"
    echo "  --shell          Open shell in laravel container"
    echo "  --npm <cmd>      Run npm command in container"
    echo "  --artisan <cmd>  Run artisan command in container"
    echo "  --help           Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                       # Standard dev setup with migrations"
    echo "  $0 --fresh               # Fresh install with seed data"
    echo "  $0 --build-only          # Just build containers"
    echo "  $0 --shell               # Access container shell"
    echo "  $0 --npm run build       # Run npm build"
    echo "  $0 --artisan tinker      # Run artisan tinker"
    echo "  $0 --down                # Stop everything"
    echo ""
    echo "Development features:"
    echo "  - Live code editing (volumes mounted)"
    echo "  - Vite HMR on port 5173"
    echo "  - Xdebug enabled"
    echo ""
}

print_success_message() {
    echo ""
    echo "============================================"
    log_success "Development environment ready!"
    echo "============================================"
    echo ""
    echo "Application:  http://localhost:${APP_PORT:-8000}"
    echo "Vite HMR:     http://localhost:5173"
    echo ""
    echo "Features enabled:"
    echo "  ✓ Live code reload (PHP, Blade, config)"
    echo "  ✓ Vite HMR for CSS/JS"
    echo "  ✓ Xdebug for debugging"
    echo ""
    echo "Useful commands:"
    echo "  $0 --shell                 # Container shell"
    echo "  $0 --artisan <cmd>         # Run artisan"
    echo "  $0 --npm <cmd>             # Run npm"
    echo "  $0 --logs                  # View logs"
    echo "  $0 --down                  # Stop containers"
    echo ""
}

main() {
    local run_migrations=true
    local run_seeders=false
    local fresh_install=false
    local build_only=false
    
    cd "$PROJECT_DIR"
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --fresh)
                fresh_install=true
                shift
                ;;
            --no-migrate)
                run_migrations=false
                shift
                ;;
            --seed)
                run_seeders=true
                shift
                ;;
            --build-only)
                build_only=true
                shift
                ;;
            --down)
                log_info "Stopping containers..."
                docker compose -f docker-compose.yml -f docker-compose.dev.yml down
                log_success "Containers stopped"
                exit 0
                ;;
            --logs)
                docker compose -f docker-compose.yml -f docker-compose.dev.yml logs -f
                exit 0
                ;;
            --shell)
                docker compose -f docker-compose.yml -f docker-compose.dev.yml exec laravel bash
                exit 0
                ;;
            --npm)
                shift
                docker compose -f docker-compose.yml -f docker-compose.dev.yml exec laravel npm "$@"
                exit 0
                ;;
            --artisan)
                shift
                docker compose -f docker-compose.yml -f docker-compose.dev.yml exec laravel php artisan "$@"
                exit 0
                ;;
            --help|-h)
                show_help
                exit 0
                ;;
            *)
                log_error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
    
    echo ""
    echo "============================================"
    echo "   Recruivo Development Environment"
    echo "============================================"
    echo ""
    
    check_docker
    check_env_file
    generate_app_key
    build_and_start
    
    if [ "$build_only" = "true" ]; then
        log_success "Build complete (setup skipped)"
        exit 0
    fi
    
    wait_for_container "mysql" "mysqladmin ping -h localhost"
    wait_for_container "redis" "redis-cli ping"
    
    run_setup "$run_migrations" "$run_seeders" "$fresh_install"
    
    print_success_message
}

main "$@"
