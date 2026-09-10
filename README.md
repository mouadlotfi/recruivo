# Recruivo — Job Board & Candidate Management

Recruivo is a full‑featured recruitment platform and candidate management marketplace built with Laravel 13, Inertia.js 3, Vue 3, TypeScript, and Tailwind CSS. It supports role‑based access (Admin, Recruiter, Candidate), rich application review pipelines, interview scheduling, note templates, multi-language localization (English, French), and dark mode.

The application serves two distinct purposes from **one unified codebase**:
1. **Production Platform**: Real recruitment operations, real users, transactional SMTP email, isolated persistent storage, and deliberate schema migrations.
2. **Public Demo Environment**: Fully interactive demonstration platform with a rich, realistic canonical dataset, demo badges, read-only demo accounts, safe email logging, isolated infrastructure, and automated nightly resets.

---

## Environment Architecture

```text
                           ONE RECRUIVO CODEBASE
                                     |
               +---------------------+---------------------+
               |                                           |
             DEMO                                      PRODUCTION
               |                                           |
         APP_ENV=demo                               APP_ENV=production
         APP_DEBUG=false                            APP_DEBUG=false
         APP_IS_DEMO=true                           APP_IS_DEMO=false
               |                                           |
         Isolated DB (demo_db)                      Production DB
         Isolated Redis                             Production Redis
         Isolated Demo Storage                      Production Storage
         Safe Email (log/sandbox)                   Real SMTP Email
         Canonical Seeded Dataset                   Real User Data
         Periodic Reset (`demo:reset`)              Deliberate Migrations Only
```

### Complete Isolation Guarantees
- **No Shared Databases**: Demo operates on its own database volume and schema.
- **No Shared Redis**: Demo cache, queues, and sessions run in separate Redis storage.
- **No Shared Uploads**: Candidate resumes and recruiter uploads in demo are isolated from production storage.
- **Email Safety**: Demo emails are captured in logs or sandbox sinks, never dispatched to real recipients.
- **Secret Isolation**: Independent `APP_KEY` and credentials per environment.
- **Production Guard**: Destructive demo commands (`php artisan demo:reset`) strictly refuse to execute in `APP_ENV=production`.

---

## Canonical Seeded Dataset

The application uses one canonical seed pipeline (`php artisan migrate --seed` or `php artisan db:seed`) providing:

- **15 Canonical Tech Companies**: Preserving original company identities and brand logos (`Aetheris Dynamics`, `BitForge Software`, `CipherWave Security`, `DataVortex Systems`, `EchoLogic AI`, `FluxCore Technologies`, `GigaByte Foundry`, `Hyperion Networks`, `IonSphere Labs`, `Krypton Solutions`, `Lumina Software House`, `NexusNode Tech`, `OmniStack Engineering`, `PixelCraft Digital`, `QuantumLeap IT`).
- **Diverse Job Catalog**: Varied IT categories (`Software Development`, `Cloud Computing`, `Cybersecurity`, `Data Analytics`, `AI/ML`, `DevOps`, `IoT`, `Quantum Computing`), realistic salary tiers ($65k–$220k), employment types (remote, hybrid, onsite), active jobs, closing-soon listings, and drafts.
- **Realistic Candidate Profiles**: 50+ candidates with structured work histories, education, multilingual proficiencies, profile links, preferred categories, and sample resumes on private storage.
- **Active Application Pipelines**: Candidates distributed across recruitment stages (Pending, Shortlisted, Interview, Accepted, Rejected, Withdrawn) with recruiter notes, interview schedules (remote links, onsite rooms), and timeline audit events.
- **Recruiter Productivity Tools**: Recruiter note templates for phone screens, technical reviews, and offer discussions.
- **Candidate Activity**: Saved jobs, status update notifications, and profile completion tracking.

---

## Demo Accounts

The following demo accounts are available in development and demo environments:

| Role | Email | Password | Details |
|------|-------|----------|---------|
| **Admin** | `admin@recruivo.work` | `password` | System overview, job moderation, user administration |
| **Recruiter** | `recruiter@recruivo.work` | `password` | Aetheris Dynamics hiring pipeline, applicants, note templates |
| **Candidate** | `candidate@recruivo.work` | `password` | Senior Full-Stack Engineer profile, active applications, saved jobs |

*Demo accounts are protected by model and controller guards against deletion and credential tampering.*

---

## Quick Start (Local Development)

### Prerequisites
- Docker & Docker Compose **or** PHP 8.3+, Composer, Node.js 20+, Bun, MySQL 8, Redis 7

### Local Setup with Docker (Recommended)

1. Configure development environment:
   ```bash
   cp .env.example .env
   ```
2. Start the development stack:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
   ```

3. Run migrations and canonical seed:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml exec app php artisan migrate --seed
   ```

4. Access the application:
   - Web App: `http://localhost:8000`
   - Vite HMR: `http://localhost:5173`

---

## Demo Environment Operations

### Running Demo with Docker

1. Create demo environment file:
   ```bash
   cp .env.example .env.demo
   # Set APP_ENV=demo, APP_IS_DEMO=true, DEMO_SCHEDULED_RESET=true
   ```

2. Launch isolated demo stack:
   ```bash
   APP_ENV_FILE=.env.demo docker compose --env-file .env.demo up -d --build
   ```

3. Seed canonical demo data:
   ```bash
   APP_ENV_FILE=.env.demo docker compose --env-file .env.demo exec app php artisan migrate:fresh --seed --force
   ```

> `APP_ENV_FILE` is the file the **containers** read (`env_file:`); `--env-file` only
> feeds compose **interpolation** (`APP_PORT`, `DB_HOST`, `REDIS_HOST`,
> `COMPOSE_PROFILES`). Pass both, as above — with `--env-file` alone the containers
> silently fall back to `.env`, and with `env_file:` alone `DB_HOST`/`REDIS_HOST`
> set in your deployment file are discarded.

### Demo Reset Command

To restore the demo environment to its clean canonical seeded state at any time:

```bash
php artisan demo:reset --force
```

Or within Docker:
```bash
APP_ENV_FILE=.env.demo docker compose --env-file .env.demo exec app php artisan demo:reset --force
```

*The demo reset command automatically clears application caches, re-runs fresh canonical migrations and seeders, re-syncs brand assets and sample resumes, and flushes cache stores. It is hard-blocked from running in production.*

---

## Production Deployment

Production architecture and deployment procedures are documented in detail in [docs/docker.md](docs/docker.md).

### Summary Checklist:
1. Copy template and supply real secrets:
   ```bash
   cp .env.example .env.production
   # Set APP_ENV=production, real secrets, and production domain
   ```
2. Build and start production stack:
   ```bash
   APP_ENV_FILE=.env.production docker compose --env-file .env.production up -d --build
   ```
3. Run deliberate production migrations (never fresh/seed):
   ```bash
   APP_ENV_FILE=.env.production docker compose --env-file .env.production run --rm --no-deps app php artisan migrate --force
   ```

4. Schedule backups **before** real data arrives — `mysql_data` and `app_storage`
   hold the only copy of applications and resumes:
   ```bash
   APP_ENV_FILE=.env.production BACKUP_REMOTE=user@backup-host:/srv/backups/recruivo ./scripts/backup.sh
   ```
   See [Backups & Restore](docs/docker.md#7-backups--restore) for retention, cron
   and the restore procedure.

> Both flags are required for the same reason as the demo stack above: `APP_ENV_FILE`
> configures the containers, `--env-file` configures compose. Deployments must also
> point `APP_ENV_FILE` at the deployment file (`/mnt/hdd2-data/containers/recruivo/.env`
> for CI) — see `.github/workflows/ci.yml`.

---

## CI/CD Pipeline (GitHub Actions + GHCR + Docker Compose)

Recruivo deploys straight from `main` onto the two hosts' Docker Compose stacks:

```text
                         GitHub
                           |
                    GitHub Actions
              +------------+------------+
              |            |            |
            Tests        Lint       Typecheck
              |            |            |
              +------------+------------+
                           |
                     Docker Build
                           |
                     PR -> STOP
                           |
                         main
                           |
                    Build Images
                           |
                         GHCR
               ghcr.io/...:sha-<SHA>
               ghcr.io/...:latest
                           |
        self-hosted runner (Linux, X64, recruivo)
                           |
             +-------------+-------------+
             |                           |
      Deploy Production             Deploy Demo
      recruivo/.env                 recruivo-demo/.env
      APP_IMAGE=...:sha-<SHA>       APP_IMAGE=...:sha-<SHA>
             |                           |
       wait for migrate             wait for migrate
             |                           |
      gate on /api/health          gate on /api/health
             |                           |
       rollback on failure          rollback on failure
             |                           |
         Production                     Demo
```

### Key Features
- **Immutable Image Tags**: Every build publishes `sha-${{ github.sha }}` (plus
  `latest`) to GHCR, and each deploy pins the app image to that exact reference, so
  a release is always the artifact that was built from that commit.
- **Self-hosted deployment**: The deploy jobs run on the `recruivo` runner on the
  deployment host, which owns the compose stacks - there is no external control
  plane between GitHub and Docker.
- **Migration Gate**: `migrate` runs as its own compose service; the deploy waits
  for its exit code before probing health.
- **Health Verification Gate**: Deployments only complete when `/api/health`
  returns `200 OK` with `"status":"healthy"`; otherwise the app is rolled back to
  the previous image tag and the job fails.
- **Rollback**: See [Rollback Procedure](docs/docker.md#6-rollback-procedure).

---

## Testing & Quality Assurance

Run the test suite locally:
```bash
# Via Docker
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm app php artisan test --compact

# Code styling
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm app vendor/bin/pint --format agent

# Frontend type checking and bundle build
bun run typecheck
bun run build
```
## License

This project is licensed under the GNU General Public License v3.0 (GPL-3.0). See the [LICENSE](LICENSE) file for details.
