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

Docker Compose is the only Docker wrapper. Persistent data stays in relative host directories:

- ./storage — Laravel uploads, logs, cache, and sessions
- ./data/mysql — MySQL data
- ./data/redis — Redis data

Create the environment file before the first run:

    cp .env.docker.example .env.docker
    mkdir -p data/mysql data/redis

Set APP_KEY in .env.docker before starting the application.
Docker commands use .env.docker; keep .env for non-Docker Laravel deployments.

### Production or staging

    docker compose --env-file .env.docker up -d --build
    docker compose --env-file .env.docker run --rm laravel php artisan migrate --force

Queued notifications can be enabled with:

    docker compose --env-file .env.docker --profile queue up -d

### Development

The development image includes Composer development dependencies and Xdebug. Code and asset changes require an image rebuild:

    docker compose --env-file .env.docker -f compose.yaml -f compose.dev.yaml up -d --build
    docker compose --env-file .env.docker -f compose.yaml -f compose.dev.yaml run --rm laravel php artisan migrate --seed

### Common commands

    docker compose --env-file .env.docker logs -f
    docker compose --env-file .env.docker exec laravel bash
    docker compose --env-file .env.docker run --rm laravel php artisan test
    docker compose --env-file .env.docker down

docker compose down stops containers but leaves ./storage and ./data/ intact. Do not remove those directories unless you intentionally want to delete application data.

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
