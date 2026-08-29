# Docker development and production

Recruivo uses one Laravel application image for PHP-FPM, the queue worker, the scheduler, and the production cache-warmup step. Nginx is a separate image built from the same Dockerfile and serves the baked `public/` tree.

## Architecture

```text
host / reverse proxy
        |
      nginx :80
        |
      app (PHP-FPM :9000)
       /             \
   mysql             redis
                       |
                  queue worker

                 scheduler
```

The Docker networks are split into:

- `recruivo_public`: Nginx and the optional development Vite server.
- `recruivo_private`: PHP-FPM, MySQL, Redis, the queue worker, and the scheduler. It is an internal Docker network.

TLS is intentionally outside this stack. Put Nginx behind the existing Caddy, Traefik, Cloudflare, load balancer, or other TLS terminator when deploying one.

## Versions and application behavior

The image follows the versions already required or used by the application:

- Laravel 13.25
- PHP 8.4
- MySQL 8.0
- Redis 7
- Node 20 for Vite
- Vue 3 with Inertia

The application currently uses Redis for cache, sessions, and queues in `.env.docker`. There are no Laravel scheduled tasks today. Queueable work is notification-driven, and the queue worker uses a 60-second timeout, below Laravel's 90-second Redis `retry_after` setting.

Sensitive resumes stay on Laravel's private disk at `storage/app/private`. Public company logos and other public files use `storage/app/public`. `public/storage` is created as the normal Laravel storage link.

## Persistent data and safety rules

The current application data is deliberately reused:

| Data | Current location | New configuration |
| --- | --- | --- |
| MySQL database | Docker volume `recruivo_mysql_august_verified` | Reused as `mysql_data` |
| Laravel uploads, private resumes, logs | repository bind mount `./storage` | Same bind mount, writable by PHP |
| Public files | `./storage/app/public` | Served through the storage link |
| Redis AOF/RDB state | `./data/redis` | Same bind mount at `/data` |

Older volumes such as `recruivo_mysql_data`, `recruivo_mysql_august_rebuild`, `recruivo_mysql_recovery_check`, `recruivo_redis_data`, `recruivo_storage_data`, and their inspection copies are not referenced by the new Compose files. They must remain until their contents have been reviewed and a separate deletion decision has been made.

The MySQL volume is declared external on purpose. A missing or misspelled volume name fails Compose startup instead of creating a new empty database. On a genuinely new installation, create the intended empty volume explicitly before the first start and never point it at an existing production name by accident:

```bash
docker volume create recruivo_mysql_august_verified
```

Never use these commands against this project without a separate, verified backup and an explicit data-removal decision:

```bash
docker compose down -v
docker volume prune
docker system prune --volumes
docker volume rm ...
```

## Environment files

Do not commit `.env`, `.env.docker`, or a production environment file. Copy the example only when the target file does not already exist:

```bash
test -f .env.docker || cp .env.docker.example .env.docker
```

Important variables include:

- `APP_KEY`, `APP_URL`, `APP_ENV`, and `APP_DEBUG`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and optionally `DB_ROOT_PASSWORD`
- `MYSQL_VOLUME_NAME`, which defaults to the existing `recruivo_mysql_august_verified`
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_DB`, `REDIS_CACHE_DB`, `REDIS_QUEUE`, and `REDIS_QUEUE_RETRY_AFTER`
- `QUEUE_CONNECTION`, `QUEUE_FAILED_DRIVER`, and `QUEUE_WORKER_TIMEOUT`
- `MAIL_*` variables for the configured SMTP provider
- `VITE_HMR_HOST` for a browser connecting from another machine during development

The development stack defaults its service environment file to `.env.docker`. The production overlay defaults it to `.env.production`. Set the optional `APP_ENV_FILE` shell variable only when the environment file has another path. The commands below pass the same file to Compose for interpolation and service environment values.

## Development

Development mounts the repository into the PHP and Vite containers. Composer and Node dependencies live in named volumes so a source mount does not hide them. Vite listens on port 5173 and PHP traffic goes through Nginx on port 8000 by default.

Start the stack:

```bash
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml up -d
```

Build after changing the Dockerfile or dependencies:

```bash
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml up -d --build
```

Run development commands:

```bash
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml exec app php artisan migrate
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml exec app php artisan schedule:list
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml exec app php artisan queue:failed
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml exec app composer install
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml exec app php artisan tinker
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml logs -f app queue scheduler nginx
```

A code edit is reflected through the source mount. A dependency or PHP extension edit requires the development rebuild. Set `VITE_HMR_HOST` to the host's LAN address when the browser is not on the Docker host.

Development publishes MySQL and Redis ports for local tools. Production does not publish either port.
## Public Demo environment

The demo environment runs from the same production Docker image but uses `compose.demo.yml` with separate data volumes, safe email logging, and `APP_ENV=demo`:

```bash
cp .env.demo.example .env.demo
docker compose --env-file .env.demo -f compose.yml -f compose.demo.yml up -d --build
```

Seed or reset the demo canonical dataset:

```bash
docker compose --env-file .env.demo -f compose.yml -f compose.demo.yml exec app php artisan demo:reset --force
```

The demo overlay binds to port `${DEMO_APP_PORT:-8001}`, configures `MAIL_MAILER=log`, isolates MySQL (`demo_mysql_data`), Redis (`demo_redis_data`), and storage (`demo_storage`), and enables automated nightly resets via the scheduler.


## Production build and deployment

The production image contains Composer dependencies without `require-dev` and the compiled Vite assets. It contains no `.env`, Node runtime, host `vendor/`, host `node_modules/`, or mutable upload files.

Create a production environment file outside version control. It must contain a real `APP_KEY`, production database credentials, production mail settings, and `APP_ENV=production` / `APP_DEBUG=false`. Do not copy the development secrets into production.

Build the images:

```bash
docker compose --env-file .env.production -f compose.yml -f compose.prod.yml build
```

Start only the data services first:

```bash
docker compose --env-file .env.production -f compose.yml -f compose.prod.yml up -d mysql redis
```

Back up the database before a migration:

```bash
mkdir -p backups
docker compose --env-file .env.production -f compose.yml -f compose.prod.yml exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --databases "$MYSQL_DATABASE" --single-transaction --routines --events --triggers --hex-blob --set-gtid-purged=OFF' > "backups/recruivo_$(date +%Y%m%d_%H%M%S).sql"
test -s backups/*.sql
```

Run migrations once, not from every long-running container:

```bash
docker compose --env-file .env.production -f compose.yml -f compose.prod.yml run --rm --no-deps app php artisan migrate --force
```

Start the production services. The `bootstrap` service runs `config:cache` and `view:cache` once with the runtime environment, then the PHP-FPM, queue, and scheduler services share those generated files. Route caching is intentionally not enabled because this application currently defines closure routes.

```bash
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml up -d --force-recreate
```

Check the deployment:

```bash
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml ps
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml logs --tail=100 nginx app queue scheduler
curl -fsS http://127.0.0.1:${APP_PORT:-8000}/api/health
```

On a later image-only deployment, back up first, build the new image, run the one migration step if migrations changed, and use `up -d --force-recreate` again. Do not use `migrate:fresh`, `db:wipe`, or `migrate:refresh` against this database.

## Queue and scheduler operations

The queue worker uses the same application image as PHP-FPM:

```bash
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml exec app php artisan queue:failed
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml exec app php artisan queue:retry all
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml restart queue
```

The scheduler is a dedicated `php artisan schedule:work` process. Laravel remains the single source of truth for schedules:

```bash
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml exec app php artisan schedule:list
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml logs -f scheduler
```

No speculative scheduled jobs were added. If a real scheduled command is added later, define it in the application's normal Laravel schedule and redeploy the scheduler container.

## Backups and restoration

### Database

The native database backup is a logical SQL dump. Verify it before storing it:

```bash
test -s backups/recruivo_YYYYMMDD_HHMMSS.sql
sha256sum backups/recruivo_YYYYMMDD_HHMMSS.sql
```

Restore only during a planned maintenance window, after taking one more backup:

```bash
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml stop nginx app queue scheduler bootstrap
docker compose --env-file .env.production -f compose.yml -f compose.prod.yml up -d mysql
cat backups/recruivo_YYYYMMDD_HHMMSS.sql | docker compose --env-file .env.production -f compose.yml -f compose.prod.yml exec -T mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml up -d
```

### Persistent files and Docker volumes

Export a named volume without changing it:

```bash
mkdir -p backups/volumes
docker run --rm \
  --mount type=volume,source=recruivo_mysql_august_verified,destination=/data,readonly \
  --mount type=bind,source="$PWD/backups/volumes",destination=/backup \
  alpine:latest sh -c 'tar -czf /backup/recruivo_mysql_august_verified.tar.gz -C /data .'
test -s backups/volumes/recruivo_mysql_august_verified.tar.gz
```

Export the persistent bind mounts:

```bash
mkdir -p backups/binds
tar -czf backups/binds/storage.tar.gz -C storage .
tar -czf backups/binds/data.tar.gz -C data .
```

Restore files into a staging directory first, compare the contents, and only then replace the live bind mount during a maintenance window. For a Docker volume restore, create a new volume, extract into that new volume, point `MYSQL_VOLUME_NAME` at the new name, and verify it before considering the legacy volume for removal. Never empty the current live volume as a shortcut.

## Rollback

1. Stop the new services without `-v`.
2. Keep `recruivo_mysql_august_verified`, `storage/`, and `data/redis/` untouched.
3. Restore the legacy Compose files from the migration backup or the previous repository revision.
4. Start the legacy stack with the same database volume and bind mounts.
5. If a migration must be reversed, use a reviewed rollback migration or restore the database dump. Do not guess at destructive rollback commands.

For a code-only rollback, restore the previous image tag and recreate only the stateless app, queue, scheduler, and Nginx containers. Keep the database and persistent storage resources unchanged.
