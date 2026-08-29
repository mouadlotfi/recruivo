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
   cp .env.docker.example .env.docker
   ```

2. Start the development stack:
   ```bash
   docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml up -d --build
   ```

3. Run migrations and canonical seed:
   ```bash
   docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml exec app php artisan migrate --seed
   ```

4. Access the application:
   - Web App: `http://localhost:8000`
   - Vite HMR: `http://localhost:5173`

---

## Demo Environment Operations

### Running Demo with Docker

1. Create demo environment file:
   ```bash
   cp .env.demo.example .env.demo
   # Review and customize .env.demo as needed
   ```

2. Launch isolated demo stack:
   ```bash
   docker compose --env-file .env.demo -f compose.yml -f compose.demo.yml up -d --build
   ```

3. Seed canonical demo data:
   ```bash
   docker compose --env-file .env.demo -f compose.yml -f compose.demo.yml exec app php artisan migrate:fresh --seed --force
   ```

### Demo Reset Command

To restore the demo environment to its clean canonical seeded state at any time:

```bash
php artisan demo:reset --force
```

Or within Docker:
```bash
docker compose --env-file .env.demo -f compose.yml -f compose.demo.yml exec app php artisan demo:reset --force
```

*The demo reset command automatically clears application caches, re-runs fresh canonical migrations and seeders, re-syncs brand assets and sample resumes, and flushes cache stores. It is hard-blocked from running in production.*

---

## Production Deployment

Production architecture and deployment procedures are documented in detail in [docs/docker.md](docs/docker.md).

### Summary Checklist:
1. Copy template and supply real secrets:
   ```bash
   cp .env.production.example .env.production
   # Populate APP_KEY, secure DB credentials, real SMTP settings, and production domain
   ```
2. Build production image:
   ```bash
   docker compose --env-file .env.production -f compose.yml -f compose.prod.yml build
   ```
3. Start database and cache services:
   ```bash
   docker compose --env-file .env.production -f compose.yml -f compose.prod.yml up -d mysql redis
   ```
4. Run deliberate production migrations (never fresh/seed):
   ```bash
   docker compose --env-file .env.production -f compose.yml -f compose.prod.yml run --rm --no-deps app php artisan migrate --force
   ```
5. Start all production application services:
   ```bash
   docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml up -d --force-recreate
   ```

---

## Testing & Quality Assurance

Run the test suite:
```bash
# Via Docker
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml run --rm app php artisan test --compact

# Code styling
docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml run --rm app vendor/bin/pint --format agent

# Frontend type checking and bundle build
bun run typecheck
bun run build
```

---

## License

This project is licensed under the GNU General Public License v3.0 (GPL-3.0). See the [LICENSE](LICENSE) file for details.
