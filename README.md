<!-- prettier-ignore -->
<div align="center">

[![CI](https://img.shields.io/github/actions/workflow/status/mouadlotfi/recruivo/ci.yml?branch=main&label=CI&logo=githubactions&logoColor=white&style=flat-square)](https://github.com/mouadlotfi/recruivo/actions/workflows/ci.yml)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white&style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white&style=flat-square)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-4169E1?logo=postgresql&logoColor=white&style=flat-square)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white&style=flat-square)
![License](https://img.shields.io/badge/License-PolyForm%20Strict%201.0.0-orange?style=flat-square)

[Run it locally](#run-it-locally) • [What's inside](#whats-inside) • [Environments](#two-environments-one-codebase) • [Delivery pipeline](#delivery-pipeline) • [Backups](#backups-and-restore) • [Tests](#tests-and-quality)

</div>

Recruivo is a job board with a lightweight applicant-tracking system. Candidates apply for
jobs, recruiters publish them and run hiring pipelines, admins operate the marketplace.

One Laravel codebase serves the web app and a JSON API. It runs in two places. The live site
is [recruivo.tech](https://recruivo.tech), and a public demo anyone can log into is
[demo.recruivo.tech](https://demo.recruivo.tech).

> [!NOTE]
> The web app is the product. The JSON API runs alongside it on Sanctum and backs the public
> listings, the search autocomplete, and the recruiter and admin endpoints. FrankenPHP serves
> both from one container image, running the same Laravel application.

## Features

- **A six-stage hiring pipeline.** `pending → shortlisted → interview → accepted`, with
  `rejected` and `withdrawn` branches. Every transition writes an `ApplicationStatusEvent`
  audit row and notifies the candidate in-app.
- **Ranked search.** `SmartSearchService` scores matches in PHP. The title weighs most, then
  the company, category, location, remote type and description. It expands synonyms,
  tolerates small typos, and suggests a correction when a text query returns nothing. Tuning
  lives in `config/search.php`.
- **A JSON API.** 33 routes under `/api` on Sanctum. They cover public job and company
  listings, the search autocomplete, recruiter job CRUD and applications, candidate resumes,
  profile management and admin user administration.
- **Two locales.** English and French, on locale-prefixed routes with a persisted `locale`
  cookie.
- **Role-based access.** `Admin`, `Recruiter` and `Candidate` come from
  `spatie/laravel-permission`. The web controllers, the API controllers and the policies all
  enforce them.
- **Operational by default.** A health endpoint gates every deploy. The app has a queue, a
  scheduler, and integrity-checked backups that encrypt with a passphrase you set and copy to
  another host. The demo cannot reach production.

## Prerequisites

Docker is all you need to run it locally. Working without it needs PHP 8.3+ with
`pdo_pgsql`, `redis`, `mbstring`, `bcmath`, `gd`, `exif`, `pcntl` and `zip`; Composer 2;
Node 22 or Bun; PostgreSQL 17 and Redis 7.

## Run it locally

For working on the code. Docker brings up the app, PostgreSQL, Redis, Mailpit and Vite in one
command, and `scripts/dev.sh` wraps it. The script creates `.env` from `.env.example`,
generates the app key before the containers start, applies the dev compose overlay, and waits
for `/api/health` to answer.

```bash
git clone https://github.com/mouadlotfi/recruivo.git && cd recruivo
./scripts/dev.sh up
./scripts/dev.sh seed
```

The same script runs the demo stack with `./scripts/dev.sh demo up`, pointed at its own
database and Redis databases, and `demo seed` loads the canonical dataset. Other subcommands
are `logs`, `shell`, `restart`, `down` and `artisan <command>`, and the header comment lists
every form, including the two-file `docker compose` invocation it wraps.

Open **<http://localhost:8000>** and log in as any of the three roles:

| Role | Email | Password | What you land on |
| --- | --- | --- | --- |
| **Admin** | `admin@recruivo.tech` | `password` | Platform overview, job moderation, user administration |
| **Recruiter** | `recruiter@recruivo.tech` | `password` | Aetheris Dynamics pipeline, applicants, note templates |
| **Candidate** | `candidate@recruivo.tech` | `password` | A senior full-stack profile with live applications and saved jobs |

Add an administrator of your own with `make:admin`. Every flag is optional, and whatever you
leave out is prompted for, password included. That prompt hides what you type and wants at
least 8 characters, and because `dev.sh artisan` runs without a TTY you should pass
`--password` there or run the command from `./scripts/dev.sh shell`:

```bash
./scripts/dev.sh artisan make:admin --email=you@example.com --name="Your Name" --password=secret123
```

It creates the account with a verified email and the `Admin` role, and creates that role first
if the stack has none. Without Docker, run `php artisan make:admin …` the same way.

Mail never leaves the machine under Docker. Mailpit catches every message at
<http://localhost:8025>.

<details>
<summary><h3>Run without Docker</h3></summary>

Create the database so the app role owns it. The `citext` migration installs a trusted
PostgreSQL extension, and only the database owner may do that:

```bash
sudo -u postgres psql -c "create role recruivo login password 'secret' createdb;"
sudo -u postgres createdb -O recruivo recruivo_db
sudo -u postgres createdb -O recruivo recruivo_test    # phpunit.xml targets this one
```

Install dependencies, then point `.env` at your local services instead of the compose
hostnames. Set `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1` and `MAIL_MAILER=log`.

```bash
composer install
npm install                 # or use bun install
cp .env.example .env
php artisan key:generate

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
php artisan migrate --seed
php artisan storage:link    # the public disk serves the seeded company logos
```

Run the app and Vite in separate terminals:

```bash
php artisan serve           # terminal 1 -> http://localhost:8000
npm run dev                 # terminal 2 -> http://localhost:5173
```

To skip Redis entirely, set `CACHE_STORE=file`, `SESSION_DRIVER=file` and
`QUEUE_CONNECTION=sync`. For queued jobs and scheduled tasks, run `php artisan queue:work`
and `php artisan schedule:work` alongside `php artisan serve`.

</details>

### What the seed builds

`migrate --seed` seeds a marketplace with realistic volume rather than a couple of rows:

| Seeded | What it contains |
| --- | --- |
| **15 companies** | Named tech employers such as Aetheris Dynamics, BitForge Software and CipherWave Security, each shipping a brand logo as a seed asset |
| **69 jobs** | 61 published and 8 drafts; remote, hybrid and onsite; salaries from $75,000 to $185,000 |
| **15 categories** | Software development, cloud computing, cybersecurity, data analytics, AI, DevOps, hardware, networking, IoT and more |
| **46 candidates** | Structured work histories, education, language proficiencies, preferences and sample resumes on private storage |
| **21 recruiters** | Spread across the companies, with note templates for phone screens, technical assessments and offers |
| **46 applications** | Across five of the six statuses, since `withdrawn` is never seeded. Carries interview schedules, recruiter notes and timeline events |

## What's inside

| Layer | Choice |
| --- | --- |
| Runtime | PHP 8.3+, Laravel 13, FrankenPHP (Caddy) in Docker |
| Web UI | Inertia 3 + Vue 3 + TypeScript, Tailwind 3, single entry at `resources/js/app.ts` |
| Data | PostgreSQL 17, Redis 7 for cache, sessions and queues |
| Auth | Session auth for the web app, Sanctum tokens for the API |
| Authz | `spatie/laravel-permission` roles plus `JobPolicy` and `ApplicationPolicy` |
| Ops | Queue worker, scheduler, spatie backups, a `/api/health` readiness probe |

Domain notes live in [CONTEXT.md](CONTEXT.md). Design tokens and UI recipes live in
[DESIGN.md](DESIGN.md).

## Two environments, one codebase

These compose files and the CI workflow exist to run Recruivo itself. The project is a
hosted service, not a self-hosting kit. The sections below describe how the live site and
its demo are deployed and maintained, not a supported way for others to host them.

The pipeline builds one image and deploys it twice. The demo runs `APP_ENV=demo`, logs mail
instead of sending it, reseeds through `demo:reset`, and serves fictional data. Production
runs `APP_ENV=production`, sends real SMTP mail, and serves real user data. Its only schema
command is `migrate --force`.

```bash
cp .env.example .env.demo
# Set APP_ENV=demo, APP_IS_DEMO=true, DEMO_SCHEDULED_RESET=true,
# PRODUCTION_URL=https://recruivo.tech, COMPOSE_PROFILES=infra-demo, REDIS_HOST=redis-demo

APP_ENV_FILE=.env.demo docker compose --env-file .env.demo up -d --build
APP_ENV_FILE=.env.demo docker compose --env-file .env.demo exec app php artisan migrate:fresh --seed --force
```

> [!IMPORTANT]
> Both flags are required, and they do different jobs. `APP_ENV_FILE` is the file the
> **containers** read through `env_file:`. `--env-file` only feeds compose **interpolation**,
> which is where `APP_PORT`, `DB_HOST`, `REDIS_HOST` and `COMPOSE_PROFILES` come from. Pass
> only `--env-file` and the containers fall back to `.env` without warning. Pass only
> `env_file:` and the `DB_HOST` and `REDIS_HOST` in your deployment file are discarded. This
> is the mistake to expect, so check both flags when a stack reads the wrong database.

### The demo cannot touch production

`php artisan demo:reset --force` restores the canonical dataset at any time. Three
independent checks stop it from running against production, and tests cover all three.

1. **Environment guard.** Fails immediately when `APP_ENV=production`.
2. **Environment allowlist.** Only `demo`, `local`, `development` and `testing` pass.
3. **Database-name allowlist.** The database must identify itself as `demo`, `test`, `local`
   or `dev`. That direction is deliberate. Production runs on `recruivo_db`, and no list of
   production-ish words would catch that name.

Demo accounts are read-only where changes would be destructive. The `User` model and
`UserAccountDeletionService` both refuse deletion, and the app refuses profile edits, password
changes, email changes, job applications, withdrawals, saved jobs and recruiter mutations. With
`DEMO_SCHEDULED_RESET=true` the scheduler runs the reset daily at 03:00.

The two stacks are separate compose projects, each setting `COMPOSE_PROJECT_NAME`: production is
`recruivo`, the demo is `recruivo-demo`. That makes `postgres_data`, `app_storage` and the demo's
own `redis_demo_data` per-project volumes. Isolation goes further than the project name:

| Resource | Demo | Production |
| --- | --- | --- |
| Database | `recruivo_demo_db` | `recruivo_db` |
| Redis | Its own `redis-demo` container, on the `infra-demo` profile | The `redis` container, on the `infra` profile |
| Cache, sessions, queue | Redis databases 2 and 3 | Redis databases 0 and 1 |
| Mail | Logged | Real SMTP |

> [!NOTE]
> A Redis ACL user's key patterns span every logical database, so one instance cannot both let
> the demo clear its cache, which needs `FLUSHDB`, and stop it from flushing production's
> databases. That is why the demo gets its own Redis.

## Delivery pipeline

Deployment runs on a self-hosted `recruivo` runner that owns the compose stacks. GitHub
reaches Docker directly on that host, with nothing in between.

- **Immutable tags.** Every build on `main` publishes
  `ghcr.io/mouadlotfi/recruivo:sha-<commit>` plus `latest`, and each deploy pins the app image
  to that exact reference. A release is always the artifact built from that commit.
- **Migration gate.** `migrate` runs as its own compose service, and a deploy waits up to
  300s for its exit code before probing health. A migration that hangs fails the deploy
  instead of shipping.
- **Health gate.** A deploy succeeds only when `GET /api/health` answers `200` within 120s.
  The probe checks the database, pending migrations, the cache store, the app key and the
  private disk. It answers `200` only when all five pass, and `503` with `"status":"unhealthy"`
  otherwise, which stops the deploy.
- **Rollback.** Any failed gate redeploys the previous image tag and fails the job.
- **Pull requests.** Build the production image to validate the Dockerfile, then stop. No
  push, no deploy.

To deploy by hand:

```bash
cp .env.example .env.production
# Set APP_ENV=production, APP_IS_DEMO=false, real secrets and your domain

APP_ENV_FILE=.env.production docker compose --env-file .env.production up -d --build
APP_ENV_FILE=.env.production docker compose --env-file .env.production run --rm --no-deps app php artisan migrate --force
```

Production never seeds. The `production` and `demo` GitHub environments each supply their own
`APP_ENV_FILE`, so this repository stores no host path.

## Backups and restore

`postgres_data` and `app_storage` hold the only copy of applications and resumes. Schedule
backups before real data arrives:

```bash
APP_ENV_FILE=.env.production BACKUP_REMOTE=user@backup-host:/srv/backups/recruivo ./scripts/backup.sh
```

`scripts/backup.sh` dumps PostgreSQL with `pg_dump`, archives `storage/app`, then verifies
both archives are intact and non-empty. It prunes backup directories older than
`BACKUP_KEEP_DAYS`, which defaults to 7, and `0` disables pruning. `BACKUP_REMOTE` also
rsyncs the result to another machine.

```bash
APP_ENV_FILE=.env.production ./scripts/restore.sh --force ./backups/20260910T101500Z
```

`scripts/restore.sh` accepts a backup directory or a spatie `.zip` archive. It verifies the
archive before destroying anything, recreates the database, restores uploads, re-runs
migrations, and prints the health check to confirm the result.

The `offsite` compose profile schedules off-site copies. The `backup` service writes archives
on `BACKUP_CRON_EXPRESSION`, which defaults to `30 3 * * *`, and keeps
`BACKUP_RETENTION_DAYS`, which defaults to 14. Set `BACKUP_GPG_PASSPHRASE` to encrypt them, and
point the service at S3 or a remote SSH host. The Laravel scheduler also runs `backup:run` at
01:30 and `backup:monitor` at 03:00.

> [!TIP]
> Set `BACKUP_ARCHIVE_PASSWORD` in production. Without it the nightly archive is a plaintext
> zip holding the database dump and the mounted deployment env file.

## Tests and quality

```bash
# Backend: the same four gates CI runs
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm app php artisan test --compact
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm app vendor/bin/pint --test
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm app vendor/bin/phpstan analyse --memory-limit=512M
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm app composer audit

# Frontend
bun run typecheck
bun run build
```

Useful commands beyond the pipeline:

```bash
php artisan demo:reset --force                     # restore the canonical demo dataset
php artisan make:admin --email=you@example.com --name="Your Name"
php artisan roles:backfill-legacy --dry-run        # inspect accounts left role-less
php artisan queue:health
```
