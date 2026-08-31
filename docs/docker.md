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

## 3. Production Deployment (Coolify)

Production uses the exact same canonical `docker-compose.yml` file, parameterized by Coolify's environment variables:

- `APP_ENV=production`
- `APP_IS_DEMO=false`
- `APP_URL=https://recruivo.work`
- `APP_PORT=8085`
- `MAIL_MAILER=smtp`

Deployments are triggered automatically by GitHub Actions or via the Coolify dashboard.

---

## 4. Demo Environment (Coolify)

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
docker compose --env-file .env.demo exec app php artisan demo:reset --force
```

---

## 5. CI/CD & Image Immutability

GitHub Actions builds and publishes immutable OCI images to GitHub Container Registry on every push to `main`:

- `ghcr.io/mouadlotfi/recruivo:sha-${GITHUB_SHA}`

Deployments update `APP_TAG=sha-${GITHUB_SHA}` in Coolify and verify `/api/health` before completing.

---

## 6. Rollback Procedure

To roll back a deployment to any previous commit:

1. Locate the desired previous Git commit SHA (e.g. `abc1234`).
2. In Coolify, update `APP_TAG` to `sha-abc1234`.
3. Click **Deploy** in Coolify.
4. Coolify pulls the immutable image from GHCR and recreates the containers instantly without rebuilding.
