# Recruivo — Job Board & Candidate Management

Recruivo is a full‑featured job board and candidate management platform built with Laravel 12. It includes role‑based access (Admin, Recruiter, Candidate), email verification, job postings, applications, and an admin area.

Application runs locally or via Docker (PHP 8.2 + Apache, MySQL 8, Redis 7). This README covers features, setup, Docker deployment, demo accounts, and common commands.

## Features

- Role-based access with `spatie/laravel-permission` (Admin, Recruiter, Candidate)
- Recruiters: create, publish/unpublish, and manage jobs; review applications, download resumes
- Candidates: browse/search jobs, apply, manage profile and resume
- Admin: basic user management dashboard
- Email verification flow
- Modern asset pipeline with Vite and Tailwind CSS
- Multi-language support (English, French)
- Dark mode support

## Tech Stack

- PHP 8.2, Laravel 12
- MySQL 8, Redis 7
- Node.js 20, Vite, Tailwind CSS
- Packages: Sanctum, Spatie Permission, Translatable

---

## Quick Start (Local Development)

Prerequisites: PHP 8.2+, Composer, Node.js 18/20+, MySQL 8, Redis (optional)

1) Install dependencies
```bash
composer install
npm install
```

2) Configure environment
```bash
cp .env.example .env
php artisan key:generate
```
Update `.env` with your local DB credentials. For email verification to work, configure your SMTP credentials:
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@recruivo.work
MAIL_FROM_NAME="${APP_NAME}"
```

3) Database and storage
```bash
php artisan migrate --seed
php artisan storage:link
```

4) Run the app
```bash
php artisan serve
```
Vite (choose one):
```bash
npm run dev   # Development with HMR
npm run build # Production build
```

Open: `http://localhost:8000/`

---

## Docker Setup

Two Docker workflows are available:

| Workflow | Script | Use Case |
|----------|--------|----------|
| **Production** | `./deploy/docker-start.sh` | Pre-built assets, optimized image |
| **Development** | `./deploy/docker-dev.sh` | Live editing, Xdebug |

### Production (Quick Demo)

```bash
chmod +x deploy/docker-start.sh
./deploy/docker-start.sh --fresh
```

This automatically:
- Creates `.env` from `.env.docker.example` (if missing)
- Generates `APP_KEY` (if empty)
- Builds the multi-stage Docker image with pre-built assets
- Waits for MySQL and Redis health checks
- Runs database migrations and seeders

Open: `http://localhost:8000/`

**How it works**: Assets (CSS/JS) are "baked into" the Docker image during build. If you change frontend files, you must rebuild:
```bash
./deploy/docker-start.sh --build
```

### Development (Active Coding)

```bash
chmod +x deploy/docker-dev.sh
./deploy/docker-dev.sh --fresh
```

**How it works**: Your local files are mounted into the container via volumes:

```
Your Machine                    Docker Container
─────────────                   ────────────────
./app/           ──────────►    /var/www/html/app/
./resources/     ──────────►    /var/www/html/resources/
./routes/        ──────────►    /var/www/html/routes/
./config/        ──────────►    /var/www/html/config/
```

This means:
- **PHP/Blade changes** → Instant (just refresh browser)
- **CSS/JS changes** → Run `./deploy/docker-dev.sh --npm run build`

Features:
- **Live code editing**: PHP, Blade, config changes reflect immediately
- **Xdebug**: Debugging enabled out of the box
- **Node.js inside container**: Run npm commands without local Node

### Development Commands

```bash
./deploy/docker-dev.sh --shell           # Bash shell inside container
./deploy/docker-dev.sh --artisan tinker  # Run artisan commands
./deploy/docker-dev.sh --npm run build   # Rebuild CSS/JS after changes
./deploy/docker-dev.sh --logs            # View container logs
./deploy/docker-dev.sh --down            # Stop containers
```

### Quick Comparison

| Aspect | Production | Development |
|--------|------------|-------------|
| Script | `docker-start.sh` | `docker-dev.sh` |
| PHP changes | Requires rebuild | Instant |
| CSS/JS changes | Requires rebuild | Run `--npm run build` |
| Xdebug | No | Yes |
| Use case | Demo / Deploy | Active development |

### CLI Options (both scripts)

| Option | Description |
|--------|-------------|
| `--fresh` | Fresh install with migrations and seeders |
| `--seed` | Run database seeders |
| `--no-migrate` | Skip database migrations |
| `--build` | Force rebuild containers |
| `--down` | Stop and remove containers |
| `--logs` | View container logs |
| `--help` | Show help message |

### Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Docker Compose                        │
├─────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │   Laravel   │  │    MySQL    │  │    Redis    │     │
│  │  (Apache)   │  │     8.0     │  │   7-alpine  │     │
│  │  Port 8000  │  │  Port 3306  │  │  Port 6379  │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
└─────────────────────────────────────────────────────────┘
```

| Container | Description |
|-----------|-------------|
| `recruivo` | PHP 8.2 + Apache |
| `recruivo_mysql` | MySQL 8.0 |
| `recruivo_redis` | Redis 7 (cache, sessions, queues) |

### Common Operations

```bash
# Shell access (production)
docker compose exec laravel bash

# Shell access (development)
./deploy/docker-dev.sh --shell

# Full reset (wipe all data)
docker compose down -v && ./deploy/docker-start.sh --fresh
```

---

## Demo Accounts (Seeded)

Use these to explore the app:

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@recruivo.work` | `password` |
| Recruiter | `recruiter@recruivo.work` | `password` |
| Candidate | `candidate@recruivo.work` | `password` |

---

## Testing

```bash
php artisan test
```

---

## License

This project is licensed under the GNU General Public License v3.0 (GPL-3.0). See the [LICENSE](LICENSE) file for details.
