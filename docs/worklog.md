# Recruivo — work log

Running ledger of changes made during the pre-production hardening pass, with the
evidence for each. Kept so the work can be resumed if a session's context is lost.

Baseline commit when this log started: `10453ba` (working tree clean).
Production (`recruivo.work`) and demo (`demo.recruivo.work`) are live behind
Cloudflare, running an **older image** than this tree (their `/api/health` payload
predates the hardening below). Nothing here is deployed until it is committed and
pushed to `main`.

---

## Round 1 — Audit (no changes)

Four parallel read-only audits (security, backend, frontend, infra) plus direct
verification. Result: application code was in good shape; the gaps were in the
deploy/ops layer and in Laravel-10 leftovers that Laravel 13 never loads.

Verified green at that point: 323 tests, Pint clean, Larastan clean (level 5 with
a 163-entry baseline), `vue-tsc` clean, Vite build clean, `composer audit` clean,
prod image builds and boots, no secrets in git history.

## Round 2 — Deployment blockers + health gate

1. **Deploy env file could not set `DB_HOST`/`REDIS_HOST`.**
   `docker-compose.yml` puts `${APP_ENV_FILE}` under `env_file:` and the same keys
   under `environment:`; compose `environment:` wins and interpolation never reads
   `env_file`. `.github/workflows/ci.yml` now passes `--env-file "$APP_ENV_FILE"`
   to every compose call in both deploy jobs (with a warning fallback if the file
   is missing).
   *Evidence:* `APP_ENV_FILE=/tmp/x.env docker compose config` resolved
   `DB_HOST: mysql` before, `DB_HOST: db.external.example` after.
2. **Docs configured the wrong file.** `README.md`, `docs/docker.md` and
   `.env.example` now show `APP_ENV_FILE=<file> docker compose --env-file <file>`
   and explain the difference (`env_file` = container, `--env-file` = interpolation).
3. **Scheduled demo reset never ran.** `bootstrap/app.php` uses
   `Application::configure()`, so `App\Console\Kernel` (with its `schedule()`) was
   never resolved. The schedule moved to `routes/console.php`
   (`Schedule::command('demo:reset --force')->dailyAt('03:00')->withoutOverlapping()`)
   and the dead kernel was deleted.
   *Evidence:* `php artisan schedule:list` printed "No scheduled tasks have been
   defined" before; now prints `0 3 * * * php artisan demo:reset --force` when
   `APP_ENV=demo` or `DEMO_SCHEDULED_RESET=true`, and stays empty in production.
4. **Health gate hardened** (`routes/api.php`): no longer echoes exception text;
   now also checks migration state and `APP_KEY` (encrypt/decrypt round trip).
   CI waits for the `migrate` container's exit code (bounded by `timeout 300`)
   before polling health, and no longer fails fast on transient `unhealthy`.
   *Evidence:* prod-image probe returns 200 with `migrations: ok, app_key: ok`;
   an empty `APP_KEY` now returns 503 (it used to report healthy).

Tests after round 2: **326 passed / 2434 assertions** (+3 in `HealthCheckTest`).
The pending-migration test caught a real bug in my first cut (`Migrator::paths()`
excludes the default path) — fixed with `array_merge($migrator->paths(), [database_path('migrations')])`.

## Round 3 — The four pre-launch items

5. **Backups** — new `scripts/backup.sh` / `scripts/restore.sh`, gitignored
   `backups/`, documented in `docs/docker.md` §7 and the README checklist.
   Local retention (`BACKUP_KEEP_DAYS`), optional `BACKUP_REMOTE` rsync push, loud
   warning when it is unset. Redis deliberately excluded.
   *Evidence:* round-tripped against a throwaway MySQL stack — seeded a row and a
   resume, backed up, dropped the database and wiped storage, restored, then read
   both back (`1 keep-me`, `candidate-resume-bytes`). Refusal without `--force`
   returns 1. Retention prune and the rsync branch were both exercised (which is
   how the missing-remote-directory bug was found and fixed).
6. **Trusted host + proxy scoping** — `bootstrap/app.php`: `trustProxies('*')`
   replaced by loopback + RFC1918 with an explicit forwarded-header mask, plus
   `trustHosts()`.
   *Evidence:* in the prod image, loopback/`APP_URL`/subdomains return 200 while
   `evil.example.com` and `recruivo.work.evil.com` return 400; the container
   healthcheck (Host `127.0.0.1`) stays healthy.
7. **Public API throttling** — `RateLimiter::for('api')` (120/min per user or IP)
   applied to the five public data routes; health and company logos excluded (the
   container healthcheck probes health every few seconds and a 429 would roll back
   a healthy deploy). Deleted the dead `app/Http/Kernel.php` that made
   `throttle:api` look configured.
   *Evidence:* 121st request in a burst returns 429 with `X-RateLimit-Limit: 120`;
   the budget is shared across throttled endpoints; `/api/health` still 200.
8. **Recruiter deletion no longer destroys candidate history** — migration
   `2026_09_10_100000_change_jobs_recruiter_foreign_key_to_null_on_delete.php`
   makes `jobs.recruiter_id` nullable with `nullOnDelete()` (mirroring the company
   fix), plus `tests/Feature/RecruiterDeletionTest.php`.
   *Evidence:* negative control — with the migration removed, both tests fail
   ("Failed asserting that 1 is identical to 2"); the job and application had been
   deleted. With it, they survive and the job keeps its `company_id`.

Tests after round 3: **331 passed / 2457 assertions**.

---

## Round 4 — Item 1: CSP + HSTS ✅

9. **Security headers wired** (`app/Http/Middleware/SecurityHeaders.php`, registered
   in `bootstrap/app.php` for the `web` group, no-op in `local`/`testing`).
   Deleted nothing; the previously orphaned middleware is now live, so the decoy is
   gone.
   - Policy: `default-src 'self'`, strict `script-src 'self'` (no unsafe-inline /
     unsafe-eval), `style-src 'self' 'unsafe-inline'` + Google Fonts, `object-src
     'none'`, `frame-ancestors/base-uri/form-action 'self'`.
   - HSTS `max-age=31536000`, host-only (no `includeSubDomains`/`preload` yet - those
     commit every subdomain for a year; documented ramp in docs/docker.md §8).
   - Caddy keeps ownership of `X-Frame-Options`/`X-Content-Type-Options`/
     `Referrer-Policy`/`Permissions-Policy` (it also covers static files); the first
     version duplicated them on every HTML response, which the header dump caught.
   - Tests: `tests/Feature/SecurityHeadersTest.php` (5) pins the allow-list.

   *Evidence (real Chrome via the puppeteer image, production image + seeded sqlite,
   host-network):* **0 CSP violations** on home, search and admin dashboard. The
   Inertia `<script type="application/json">` payload (14,315 chars) is read and the
   app boots under `script-src 'self'`, 12 job links render, the autocomplete issues
   its same-origin fetch (111 suggestion nodes), login works end to end and the
   admin chart canvas paints (710x288) with **no `unsafe-eval`**.
   Two things the browser caught that static review would not: the runtime-injected
   `<style>` blocks (hence `style-src 'unsafe-inline'`), and duplicate classic
   headers from Caddy + middleware.

   *Gotchas learned:* opcache runs with `validate_timestamps=0` in the prod image, so
   **a code change needs a container restart** to take effect there; and running
   `docker run` without the compose volume means seeded files land in a throwaway
   layer (company logo 404s in my first check were that, not a bug).

## Round 5 — Unplanned: dev stack could not start

10. **`docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d` failed**
    with `error from registry: unauthorized` for `ghcr.io/mouadlotfi/recruivo:latest`.
    The dev overlay overrode `image:` for `app`, `queue` and `scheduler` but not for
    `migrate`, so `migrate` still pointed at the private GHCR image (and at the
    `production` target) - compose cannot pull it without registry credentials, and
    a dev migration would have run a published image's code instead of the working
    tree.

    Fix: one `x-laravel-service-dev` anchor in `docker-compose.dev.yml` applied to
    all four Laravel services (`app`, `queue`, `scheduler`, `migrate`), so the
    duplicate-override pattern that caused the miss is gone.

    *Evidence:* resolved config (`docker compose config`) now shows every Laravel
    service as `recruivo:development` / target `development` with the source and
    vendor mounts, and no service referencing GHCR. Then the real
    `up -d --build`: all services healthy, `migrate` exited 0 (this also ran
    `2026_09_10_100000_change_jobs_recruiter_foreign_key_to_null_on_delete`
    successfully on **MySQL 8**, which the sqlite test suite cannot prove),
    `/en` 200, `/api/health` 200 with all five checks ok, Vite serving on 5173 with
    `public/hot` regenerated. `php artisan db:seed` then produced 68 users (including
    the three demo accounts), 12 jobs, 12 companies; company logos resolve (200),
    which also confirms the earlier logo 404s were the throwaway-container artifact
    and not a seeding bug.

    Local stack state after this: **running** on http://localhost:8000 (dev only;
    it is not part of the deployed environments).

## Round 6 — Item 2: docker log rotation + non-root ✅

11. **Container logs capped.** `json-file` with `max-size: 10m` / `max-file: 3` on
    every service in both compose files (previously Docker's unbounded default on
    the same disk as `mysql_data`/`app_storage`). The in-container Laravel log now
    rotates as well: the `stack` channel writes to the `daily` driver (14 days)
    instead of the framework's unbounded `single`, so growth is bounded without
    requiring an env change on the servers.
    *Evidence:* resolved config shows the options on all seven services in both
    files (`app`, `migrate`, `queue`, `scheduler`, `mysql`, `redis`, `vite`).
12. **Production runs unprivileged.** `USER www-data` in the production stage,
    with `setcap cap_net_bind_service=+ep` on the frankenphp binary so it can still
    bind `:80`, and `/data` + `/config` chowned at build time. The `development`
    target is explicitly `USER root` because it bind-mounts a developer-owned
    working tree.
    *Evidence:* `docker inspect` reports `www-data` (prod) and `root` (dev);
    `getcap` confirms the capability; a production container runs as uid 33,
    executes `migrate` against sqlite, answers `/api/health` 200 (all five checks)
    and `/en` 200, and the container healthcheck reaches **healthy** - i.e. the
    unprivileged bind on `:80` really works. The dev stack was rebuilt and
    restarted afterwards: all services healthy, `/en` 200, and `exec app id`
    still reports root, so host-mounted development is unaffected.

    *Gotcha learned:* `fs.protected_regular` on the host blocks writes to a file
    owned by another uid inside a sticky world-writable directory, and PHP's
    `is_writable()` still returns true - a bind-mounted sqlite file in host `/tmp`
    therefore fails with "attempt to write a readonly database". Create state
    inside the container instead.

## Remaining work (agreed order, one item per session)

1. ~~CSP + HSTS.~~ **done**
2. ~~Docker log rotation + non-root user.~~ **done**
3. Two live correctness bugs: `Post::scopeLatest` shadowed by Eloquent's
   `latest()`, and `Api/Recruiter/JobController::mapJobData` clearing
   `published_at` on an update that omits `status`.
4. Frontend a11y/SEO batch.
5. `AGENTS.md` refresh (says PHP 8.2 / Laravel 12; points at `resources/js/Layout/*`
   which is actually `Components/Layout/` and `Layouts/`).
6. PHPStan baseline burn-down (163 entries, ~80% from 15 untyped relations).
7. Web↔API duplication (24 near-duplicate method pairs).

Deferred / needs an owner decision: `APP_BIND_IP` (container port still published on
0.0.0.0), `BACKUP_REMOTE` destination, Sanctum token expiry, MySQL charset pinning,
`bun.lock` (CI runs bare `bun install` while the image uses `npm ci`), stale branches
(`application-pipeline`, `origin/diag/flaky-dashboard`), 900+ line AdminDashboardService.

---

## How to run things here

No PHP, Composer, Node or npm on this host (only `bun` and Docker), so everything
runs in containers:

```bash
# tests
docker run --rm -v "$PWD":/app -w /app -e APP_ENV=testing \
  -e APP_KEY=base64:7f9E2k8nZ3vB5cX7mA9pQ1wE3rT5yU7iO9pA1sD3fG5= \
  php:8.4-cli php artisan test --compact

# style / static analysis (dev deps must be installed: composer install)
docker run --rm -v "$PWD":/app -w /app php:8.4-cli php vendor/bin/pint --format agent
docker run --rm -v "$PWD":/app -w /app php:8.4-cli php vendor/bin/phpstan analyse --memory-limit=512M --no-progress

# production image
docker build --target production -t recruivo:audit .
```

Local development stack (nothing is running at the time of writing):

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

`app` on http://localhost:8000 (source bind-mounted, `APP_ENV=local`), `vite` on
5173, MySQL on 3306, Redis on 6379, plus `queue`, `scheduler` and a one-shot
`migrate`. `COMPOSE_PROFILES=infra` in `.env` is what starts MySQL/Redis.

Gotcha: `public/hot` (left over from an earlier `npm run dev`) makes Laravel load
assets from `http://localhost:5173`; with no Vite server running every page loads
unstyled. Delete `public/hot` to fall back to `public/build`, or start the stack
with the `vite` service.
