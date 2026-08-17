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

The Docker architecture and data-preservation procedures are documented in [docs/docker.md](docs/docker.md).

Create `.env.docker` only if it does not already exist, then set `APP_KEY`:

    test -f .env.docker || cp .env.docker.example .env.docker

Development:

    docker compose --env-file .env.docker -f compose.yml -f compose.dev.yml up -d

Production build and start:

    docker compose --env-file .env.production -f compose.yml -f compose.prod.yml build
    docker compose --env-file .env.production --profile production -f compose.yml -f compose.prod.yml up -d

Production migrations are a separate, deliberate step. Run a database backup first, then:

    docker compose --env-file .env.production -f compose.yml -f compose.prod.yml run --rm --no-deps app php artisan migrate --force

Persistent data is not stored in the application image. The existing MySQL volume `recruivo_mysql_august_verified`, `./storage`, and `./data/redis` must not be deleted or pruned during normal Docker cleanup.

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
