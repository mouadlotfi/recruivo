# Docker Architecture & Deployment

Recruivo uses a single canonical Docker Compose architecture (`docker-compose.yml`) for all environments: Local Development, Production, and Public Demo.

---

## 1. Core Architecture

```text
Host / Traefik Reverse Proxy (:80)
        │
      app (FrankenPHP: Caddy + PHP 8.4 :80)
       ├── mysql (:3306)
       ├── redis (:6379)
       ├── queue worker (`artisan queue:work`)
       ├── scheduler (`artisan schedule:work`)
       └── migrate (one-shot startup migrations)
```

### Services
- **`app`**: Unified FrankenPHP runtime serving static assets and executing PHP 8.4 requests in-process via embedded Caddy web server.
- **`mysql`**: MySQL 8.0 database engine.
- **`redis`**: Redis 7 cache, session, and queue backend.
- **`queue`**: Background queue worker processing asynchronous jobs.
- **`scheduler`**: Executes Laravel scheduled tasks.
- **`migrate`**: Runs one-shot database migrations on stack startup.

---

## 2. Local Development

Run the full local development stack with live source code mounting and Vite Hot Module Replacement (HMR):

```bash
cp .env.example .env
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

- **Web Application**: `http://localhost:8000`
- **Vite HMR Server**: `http://localhost:5173`
- **MySQL Direct Access**: `localhost:3306` (User: `recruivo` / Pass: `secret`)
- **Redis Direct Access**: `localhost:6379`

### Development Commands
```bash
# Run tests
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm app php artisan test --compact

# Run Pint linter
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm app vendor/bin/pint --test

# Run migrations
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec app php artisan migrate
```

---

## 3. Production Deployment (Docker Compose)

Production uses the exact same canonical `docker-compose.yml` file, parameterized by the deployment environment file:

- `APP_ENV=production`
- `APP_IS_DEMO=false`
- `APP_URL=https://recruivo.work`
- `APP_PORT=8085`
- `MAIL_MAILER=smtp`

### Environment file
Compose reads container environment from `env_file: ${APP_ENV_FILE:-.env}` but
resolves `${DB_HOST:-mysql}` / `${REDIS_HOST:-redis}` / `${COMPOSE_PROFILES}` from
compose interpolation, which only sees the shell environment or an `--env-file`.
Export both when operating a deployment manually:

```bash
APP_ENV_FILE=/path/to/production.env docker compose --env-file /path/to/production.env ps
```

CI does the same (`APP_ENV_FILE` + `--env-file` in `.github/workflows/ci.yml`).
Without it, `DB_HOST`/`REDIS_HOST` set in the deployment file are discarded and the
`infra` profile (mysql/redis containers) is never enabled.

Deployments are triggered by GitHub Actions on every push to `main` (see
`.github/workflows/ci.yml`); a manual operation uses the same compose invocation
shown above on the deployment host.

---

## 4. Demo Environment (Docker Compose)

The Demo environment deploys the exact same canonical `docker-compose.yml` file and image SHA with isolated Demo environment variables:

- `APP_ENV=demo`
- `APP_IS_DEMO=true`
- `APP_URL=https://demo.recruivo.work`
- `APP_PORT=8086`
- `MAIL_MAILER=log`
- `DEMO_SCHEDULED_RESET=true`

### Demo Reset
To manually restore the Demo database to its canonical seeded dataset:
```bash
APP_ENV_FILE=.env.demo docker compose --env-file .env.demo exec app php artisan demo:reset --force
```

> `APP_ENV_FILE` selects the file the containers read; `--env-file` feeds compose
> interpolation (`APP_PORT`, `DB_HOST`, `REDIS_HOST`, `COMPOSE_PROFILES`). Use both,
> otherwise the containers silently fall back to `.env`.

---

## 5. CI/CD & Image Immutability

GitHub Actions builds and publishes immutable OCI images to GitHub Container Registry on every push to `main`:

- `ghcr.io/mouadlotfi/recruivo:sha-${GITHUB_SHA}`

Each deploy pins both `APP_IMAGE=ghcr.io/<owner>/recruivo:sha-${GITHUB_SHA}` and
`APP_TAG=sha-${GITHUB_SHA}`, waits for the `migrate` service to exit successfully,
then verifies `/api/health` before completing.

The app image reference is resolved from `APP_IMAGE`, whose compose default appends
`APP_TAG`. A *set but untagged* `APP_IMAGE` therefore wins over that default and the
deploy silently pulls `latest` - the deploy jobs export the fully qualified reference
for exactly this reason.

---

## 6. Rollback Procedure

To roll back a deployment to any previous commit, on the deployment host:

1. Locate the desired previous Git commit SHA (e.g. `abc1234`), which must still
   have its `sha-abc1234` image in GHCR.
2. From a checkout of this repository, pin the image and recreate the containers:

   ```bash
   APP_ENV_FILE=/mnt/hdd2-data/containers/recruivo/.env \
     APP_IMAGE=ghcr.io/mouadlotfi/recruivo:sha-abc1234 APP_TAG=sha-abc1234 \
     docker compose --env-file /mnt/hdd2-data/containers/recruivo/.env \
     -p recruivo up -d --remove-orphans
   ```

   Use `-p recruivo-demo` with `/mnt/hdd2-data/containers/recruivo-demo/.env` for
   the Demo stack.
3. Docker pulls the immutable image from GHCR and recreates the containers without
   rebuilding anything.

Rollback only swaps the image tag: it does not restore data, and re-running an older
image re-applies that image's migration set on top of the current schema. Pair it with
the restore procedure below when a release has already changed data.

---

## 7. Backups & Restore

`mysql_data` (the database) and `app_storage` (candidate resumes, logos, private
uploads) hold the only copy of the platform's data. Neither is backed up
automatically — schedule the script below on the host that runs the stack:

```bash
# Dump a new backup, verify it, prune local copies older than 7 days
APP_ENV_FILE=/mnt/hdd2-data/containers/recruivo/.env ./scripts/backup.sh
```

| Variable | Default | Purpose |
|---|---|---|
| `COMPOSE_PROJECT_NAME` | `recruivo` | Compose project to back up |
| `APP_ENV_FILE` | unset | Deployment env file (also passed as `--env-file`) |
| `BACKUP_DIR` | `<repo>/backups` | Output directory, gitignored |
| `BACKUP_KEEP_DAYS` | `7` | Local retention; `0` keeps everything |
| `BACKUP_REMOTE` | unset | `rsync` target for the offsite copy |

Each run writes `<BACKUP_DIR>/<UTC timestamp>/{database.sql.gz,storage.tar.gz}`,
verifies both archives (`gzip -t`, `tar tzf`, non-empty) and fails loudly if either
is unusable. **Set `BACKUP_REMOTE`**: without it every backup lives on the same
disk as the database it protects, which does not survive disk loss. Prune the
remote copies according to your own offsite retention policy — the script only
manages local retention.

Suggested cron entry (daily at 02:30, before the demo reset at 03:00):

```cron
30 2 * * * cd /path/to/recruivo && APP_ENV_FILE=/mnt/hdd2-data/containers/recruivo/.env BACKUP_REMOTE=user@backup-host:/srv/backups/recruivo ./scripts/backup.sh >> /var/log/recruivo-backup.log 2>&1
```

### Restore

```bash
APP_ENV_FILE=/mnt/hdd2-data/containers/recruivo/.env ./scripts/restore.sh --force ./backups/20260910T101500Z
```

The restore is destructive and intentionally requires `--force` (like
`demo:reset`). It verifies both artifacts first, then recreates the database
schema the way the MySQL image created it (so table collation does not silently
change), restores the uploaded files, and runs `php artisan migrate --force` so
the schema matches the running image. Restart the stack afterwards:

```bash
docker compose -p recruivo restart app queue scheduler
```

Redis is intentionally excluded: it holds cache, sessions and the queue, all of
which are expendable compared to the data above. A restore therefore logs every
user out and may drop notifications that were still queued.

---

## 8. Security headers

Two layers, one owner per header so nothing is emitted twice:

| Header | Value | Set by |
|---|---|---|
| `Content-Security-Policy` | see below | `App\Http\Middleware\SecurityHeaders` (HTML only) |
| `Strict-Transport-Security` | `max-age=31536000` | same middleware |
| `X-Content-Type-Options` | `nosniff` | `Caddyfile` (also covers static files) |
| `X-Frame-Options` | `SAMEORIGIN` | `Caddyfile` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | `Caddyfile` |
| `Permissions-Policy` | `geolocation=(), microphone=()` | `Caddyfile` |

```text
default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self';
object-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline';
font-src 'self'; img-src 'self' data: https:; connect-src 'self'
```

Why it looks like this:

- **`script-src 'self'`, no `unsafe-inline`/`unsafe-eval`.** Inertia ships the page
  payload as `<script type="application/json">`, which browsers do not subject to
  `script-src` (verified in Chrome against the production image: the app boots and
  no `script-src` violation fires). Injected inline scripts are therefore blocked.
- **`style-src` keeps `'unsafe-inline'`.** The bundle injects `<style>` elements at
  runtime (nProgress, per-page styles); hashes change on every build and the
  injected elements cannot carry a nonce. Inline *style* is not a script-execution
  vector, and `script-src` stays strict.
- **No external hosts.** The webfonts are self-hosted as two variable files in
  `public/fonts/` (declared in `resources/css/fonts.css`, referenced by
  origin-relative paths so dev and production serve them the same way), so
  `style-src`/`font-src` need nothing beyond `'self'` and no visitor request
  leaves the origin. The filenames carry their upstream version because the
  Caddyfile caches `*.woff2` as immutable for a year.
- **`local`/`testing` are exempt.** The Vite dev server injects inline scripts and
  styles and talks over a websocket that this policy blocks, so the middleware is a
  no-op outside production/demo.

### HSTS ramp

The header is host-only today (`max-age=31536000`, no `includeSubDomains`, no
`preload`) because those directives commit **every** subdomain of the domain to
HTTPS for a year and are deliberately hard to undo. Once every subdomain
(`demo.`, any future `status.`/`assets.`) is HTTPS-only end to end, change
`STRICT_TRANSPORT_SECURITY` in `app/Http/Middleware/SecurityHeaders.php` to
`max-age=31536000; includeSubDomains` and, after that has been live for a while,
submit for preload. The same header can be set at Cloudflare instead - only one of
the two should do it.

### Verifying a change

```bash
curl -sSI https://recruivo.work/en | grep -i content-security
```

Then load the home page, the search page (autocomplete fetch) and the admin
dashboard (charts) in a browser with the devtools console open: a violated
directive is logged as `Refused to ...`/`Applying inline style ...`. Keep
`tests/Feature/SecurityHeadersTest.php` in step with any change - it pins the
allow-list, so dropping a font host or adding a wildcard has to be deliberate.

---

## 9. Runtime hardening

### Container logs are capped

Every service in both compose files uses `json-file` with `max-size: 10m` and
`max-file: 3`. Docker's default is unbounded, and these logs live on the same
disk as `mysql_data` and `app_storage` - a crash-looping worker or a noisy query
log would otherwise fill it and take the database down with it.

The in-container Laravel log rotates too: the `stack` channel writes to the
`daily` driver (14 days) instead of the framework default `single`. That file
lives in the app storage volume, which nothing prunes, so a single unbounded
`laravel.log` would grow for the lifetime of the deployment. Set
`LOG_CHANNEL=daily` explicitly if you prefer; both paths rotate now.

### Production runs unprivileged

The `production` image runs as `www-data` (uid 33). Caddy writes only to `/data`
and `/config`, PHP only to `storage/` - both chowned at build time. Because the
container still publishes `:80`, the build sets a file capability on the server
binary:

```dockerfile
setcap 'cap_net_bind_service=+ep' /usr/local/bin/frankenphp
```

so an unprivileged process may bind the privileged port without the container
running as root or needing `--privileged`.

The `development` target stays `root` on purpose: it bind-mounts the working tree
from the host, so the entrypoint must be able to reconcile ownership of
`storage/` and `bootstrap/cache` for a developer-owned checkout.

Verify a running container:

```bash
docker compose -p recruivo exec app id                  # uid=33(www-data)
docker inspect --format '{{.Config.User}}' <image>      # www-data
docker compose -p recruivo exec app getcap /usr/local/bin/frankenphp
```

If a future base image change drops the file capability, the container fails fast
with `bind: permission denied` on :80 - the healthcheck never goes green and the
deploy rolls back. In that case either re-add the capability or move the internal
port to 8080 and update the compose port mapping and both healthchecks.
