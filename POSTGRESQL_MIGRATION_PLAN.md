# MySQL → PostgreSQL, zero data loss

> **Status: complete.** The cutover ran on 2026-09-17 and the MySQL service was
> retired in `3d53c16`. Both environments serve from PostgreSQL, and every backup
> dumps with `pg_dump`. The rollback path is now the artifact rather than a running
> server: `/mnt/hdd2-data/backups/mysql-final-20260917T120055Z.sql.gz`, which holds
> both databases, all 40 tables, and the production row. This document is kept as
> the record of why the work was staged the way it was.

## Context

Recruivo runs on MySQL 8.0 (`docker-compose.yml:57-80`), and production must move to PostgreSQL without losing a row. The production database holds one account; the demo database (68 users, 69 jobs, 47 applications) is a seeded dataset that every demo deploy rebuilds via `demo:reset` (`app/Console/Commands/DemoReset.php:74` → `migrate:fresh --seed`).

Two database servers cannot both hold the truth, so the work is staged: first make the code and schema valid on both engines and ship it (production keeps serving from MySQL), then build the PostgreSQL schema, copy the rows, and flip the connection. MySQL stays installed with its volume intact as the rollback path until it is deliberately retired.

Verified facts this plan depends on:

- Only one MySQL-only SQL construct exists in the app: `PostController.php:65` `JSON_UNQUOTE(JSON_EXTRACT(...))`. Every other raw fragment (`orderByRaw('COUNT(*) DESC')`, `CASE WHEN category IN (...)`, `selectRaw('COUNT(*) as aggregate')`, `DB::select('select 1')`) is portable, and `AdminDashboardService::bucketExpression` already branches `'pgsql' => to_char(...)`.
- 26 `LIKE` call sites rely on MySQL's case-insensitive `utf8mb4_unicode_ci`; PostgreSQL's `LIKE` is case-sensitive. Laravel 13.25 ships `whereLike()`/`orWhereLike()`, which compile to `ILIKE` on PostgreSQL (`PostgresGrammar.php:93-97`) and to `LIKE` on MySQL. Verified by compiling a query with the PostgreSQL grammar: `select * from "jobs" where "title"::text ilike ?`, and `method_exists` is false on the Eloquent builder but `__call` forwarding works (the where type is `Like`).
- The `citext` extension is present in the official `postgres` image (61 rows in `pg_available_extensions` on the box's `postgres:15-alpine`), and on PostgreSQL 13+ it is a trusted extension, so the database owner can create it. `postgres:17-bookworm` and `postgres:17` exist in the registry; the image's default locale is `en_US.utf8`, encoding `UTF8` (verified: `SHOW lc_collate` → `en_US.utf8`).
- The MySQL servers run with `@@system_time_zone = UTC`, so `TIMESTAMP` values read through PDO are UTC wall clock and load into PostgreSQL `timestamp without time zone` unchanged.
- `migrate:fresh` on PostgreSQL drops tables individually via `PostgresBuilder::dropAllTables()` (`dont_drop` = `['spatial_ref_sys']`), not `DROP SCHEMA`. The `citext` extension therefore survives `demo:reset`, and `CREATE EXTENSION IF NOT EXISTS` is idempotent.
- The demo stack has no database container of its own (`COMPOSE_PROFILES` is empty in its env file); it reaches the production stack's database over the shared `recruivo_network` alias. The PostgreSQL service reproduces that arrangement.
- Deployment: `.github/workflows/ci.yml` `deploy-production` / `deploy-demo` run `docker compose --env-file "$APP_ENV_FILE" -p <project> up -d --remove-orphans`, wait for the one-shot `migrate` container, gate on container health plus `/api/health` 200, and roll the image back to `PREV_TAG` on failure. `APP_ENV_FILE` is `/mnt/hdd2-data/containers/recruivo/.env` (production) and `/mnt/hdd2-data/containers/recruivo-demo/.env` (demo).

## Approach

### 1. Make the queries driver-neutral

Ship these edits in commit 1. They are correct on MySQL and PostgreSQL, so production keeps running on MySQL while they are deployed.

1.1 `app/Http/Controllers/PostController.php:63-66` — replace the MySQL-only JSON lookup:

```php
// Find post by localized slug using JSON query for better performance.
$post = Post::published()
    ->with('user')
    ->where("slug->>{$locale}", $slug)
    ->first();
```

becomes

```php
// The JSON path is interpolated, not bound: {locale} is constrained to en|fr
// by the route group in routes/web.php:45.
$post = Post::published()
    ->with('user')
    ->where("slug->>{$locale}", $slug)
    ->first();
```

(The body is unchanged; only the comment above it changes, replacing "using JSON query for better performance" with the route-constraint note.) `slug->>en` compiles to `json_unquote(json_extract(slug, '$."en"'))` on MySQL and to `slug->>'en'` on PostgreSQL.

1.2 Replace every `LIKE` call with the case-insensitive builder method, one for one, preserving chain order and the `%…%` values:

- `->where('COL', 'like', $v)` → `->whereLike('COL', $v)`
- `->orWhere('COL', 'like', $v)` → `->orWhereLike('COL', $v)`

Exact sites (26):

| File | Lines |
|---|---|
| `app/Http/Controllers/JobController.php` | 80, 81, 82, 87 |
| `app/Http/Controllers/Api/JobController.php` | 20, 21, 22, 27 |
| `app/Http/Controllers/Api/CompanyController.php` | 23, 24, 25 |
| `app/Http/Controllers/Api/Admin/UserController.php` | 23, 24 |
| `app/Http/Controllers/Admin/JobController.php` | 29, 30, 31, 33, 37, 38 |
| `app/Http/Controllers/Admin/UserController.php` | 19, 20, 21, 23 |
| `app/Services/SmartSearchService.php` | 78, 103 |

After the edit `git grep -n "'like'" app/` must return nothing.

1.3 `config/queue.php:31` — `'database' => env('DB_CONNECTION', 'mysql')` → `env('DB_CONNECTION', 'pgsql')`.

Decision: case-insensitive matching via `ILIKE`, not `citext`, for these columns. `citext` is applied only to email columns (step 2), where equality and the unique index are the contract; `ILIKE` covers the search columns without changing their type.

### 2. Build the schema on PostgreSQL (migrations)

2.1 Switch the seven JSON columns to `jsonb` so PostgreSQL gets equality, ordering and GIN-indexable JSON (plain `json` has no equality or ordering operator, so any future `distinct()`/`groupBy()` over those columns would error). Edit these three already-applied migrations — `->jsonb()` compiles to `json` on MySQL (`MySqlGrammar::typeJsonb`), so the MySQL schema is bit-identical and the edits are inert there:

- `database/migrations/2025_01_10_000000_create_posts_table.php:19,20,21` — `json` → `jsonb` for `title`, `slug`, `content`.
- `database/migrations/2026_08_13_000000_add_structured_candidate_profile_fields.php:13,14,15,16` — `json` → `jsonb` for `languages_data`, `profile_links`, `experiences`, `educations`.
- `database/migrations/2026_08_14_120200_add_preferred_categories_to_candidate_profiles_table.php:12` — `json` → `jsonb` for `preferred_categories`.

2.2 Add `database/migrations/2026_09_11_000000_add_postgres_citext_email_columns.php` (timestamp must sort above `2026_09_10_200000`, the newest existing migration):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL compared these columns case-insensitively through utf8mb4_unicode_ci;
     * PostgreSQL does not. citext restores that: the unique index on
     * users.email and the login/reset lookups keep MySQL's semantics with no
     * query changes anywhere.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('create extension if not exists citext');
        DB::statement('alter table users alter column email type citext using email::citext');
        DB::statement('alter table password_reset_tokens alter column email type citext using email::citext');
    }

    /**
     * Reverting reintroduces case-sensitive email comparison, which is why the
     * extension is deliberately left installed.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('alter table users alter column email type varchar(255) using email::text');
        DB::statement('alter table password_reset_tokens alter column email type varchar(255) using email::text');
    }
};
```

### 3. Run the test suite and CI on PostgreSQL

3.1 `phpunit.xml` — replace lines 30-31 (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) with:

```xml
        <server name="DB_CONNECTION" value="pgsql" force="true"/>
        <server name="DB_DATABASE" value="recruivo_test" force="true"/>
```

Do **not** force `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD`: those come from the container environment locally (`DB_HOST=postgres`) and from the CI job environment (`127.0.0.1`). `force="true"` on the database name is what keeps `RefreshDatabase` away from the development database.

Note: `php artisan test --parallel` creates per-worker databases named `recruivo_test_test_<n>` (`TestDatabases::switchToDatabase`); they are created on demand and require a role with `CREATEDB`, which the superuser role provides.

3.2 `.github/workflows/ci.yml`, `backend` job:

- `timeout-minutes: 10` → `timeout-minutes: 20`.
- `extensions: mbstring, pdo_sqlite, bcmath, zip, gd, redis` → `extensions: mbstring, pdo_pgsql, bcmath, zip, gd, redis`.
- Add a `services:` block to the job:

```yaml
    services:
      postgres:
        image: postgres:17
        env:
          POSTGRES_DB: recruivo_test
          POSTGRES_USER: recruivo
          POSTGRES_PASSWORD: secret
        ports:
          - 5432:5432
        options: >-
          --health-cmd "pg_isready -U recruivo"
          --health-interval 5s
          --health-timeout 5s
          --health-retries 20
```

- The `Run tests (PHPUnit)` step `env:` becomes:

```yaml
        env:
          APP_ENV: testing
          APP_KEY: base64:7f9E2k8nZ3vB5cX7mA9pQ1wE3rT5yU7iO9pA1sD3fG5=
          DB_CONNECTION: pgsql
          DB_HOST: 127.0.0.1
          DB_PORT: 5432
          DB_DATABASE: recruivo_test
          DB_USERNAME: recruivo
          DB_PASSWORD: secret
          CACHE_DRIVER: array
          SESSION_DRIVER: array
          QUEUE_CONNECTION: sync
          MAIL_MAILER: array
```

3.3 `tests/Feature/DemoResetTest.php` — the renamed connection and the SQLite island. At lines 31-32 replace

```php
        Config::set('database.connections.mysql.database', 'recruivo_production_db');
        Config::set('database.default', 'mysql');
```

with

```php
        Config::set('database.connections.pgsql.database', 'recruivo_production_db');
        Config::set('database.default', 'pgsql');
```

and at lines 45-46 replace

```php
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('database.default', 'sqlite');
```

with

```php
        Config::set('database.connections.pgsql.database', 'recruivo_test');
        Config::set('database.default', 'pgsql');
```

(`pdo_sqlite` is no longer installed in CI after 3.2, and the mocked command never queries the database — it only reads the configured database name.)

3.4 Add one test to `tests/Feature/Auth/LoginTest.php` covering the behaviour `citext` exists for: create a verified candidate whose email is stored with mixed case (`Mixed.Case@Example.com`), then authenticate with `mixed.case@example.com` and assert the login succeeds and lands on the same user id. Without the citext migration this test fails on PostgreSQL.

### 4. Swap the infrastructure

4.1 `Dockerfile:49` — `pdo_mysql \` → `pdo_mysql \` + `pdo_pgsql \` (keep `pdo_mysql`: the legacy connection and the rollback both need it). Install both by adding `pdo_pgsql \` to the `install-php-extensions` list, keeping alphabetical order:

```dockerfile
RUN install-php-extensions \
    bcmath \
    exif \
    gd \
    mbstring \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    zip \
    redis
```

4.2 `docker-compose.yml` — add the `postgres` service next to `mysql` (do not remove `mysql` yet), and add `postgres_data` to the `volumes:` section:

```yaml
  postgres:
    image: postgres:17
    restart: unless-stopped
    logging: *default-logging
    profiles: ["infra"]
    networks:
      default:
        aliases:
          - postgres
    env_file:
      - path: ${APP_ENV_FILE:-.env}
        required: false
    environment:
      POSTGRES_DB: ${DB_DATABASE:-recruivo_db}
      POSTGRES_USER: ${DB_USERNAME:-recruivo}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-secret}
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./docker/postgres/init:/docker-entrypoint-initdb.d:ro
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U \"$${POSTGRES_USER}\" -d \"$${POSTGRES_DB}\""]
      interval: 5s
      timeout: 5s
      retries: 36
      start_period: 30s
```

The application role is the database's superuser (`POSTGRES_USER` = `DB_USERNAME`), matching the current posture where `DB_ROOT_PASSWORD` sits beside `DB_PASSWORD` in the same file. `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` already exist in both deployed env files, so no new secret is introduced and the database name stays `recruivo_db`/`recruivo_demo_db`.

In the `x-laravel-service` anchor add `postgres` alongside `mysql` in `depends_on` (`condition: service_healthy`, `required: false`), and change `DB_HOST: ${DB_HOST:-mysql}` to `${DB_HOST:-postgres}` (line 30).

4.3 `docker/postgres/init/01-create-extra-databases.sql` (new file; the entrypoint runs it only on first initialisation of an empty data directory). The guards matter: the entrypoint executes this file with `ON_ERROR_STOP=1`, so an unguarded `CREATE DATABASE` aborts the whole initialisation when the name already exists (any stack whose own `POSTGRES_DB` is `recruivo_demo_db`), and a literal `OWNER recruivo` breaks for any other `POSTGRES_USER`:

```sql
-- POSTGRES_DB (recruivo_db) is created by the entrypoint. These two are the
-- demo dataset and the test suite's database; both are owned by the role the
-- entrypoint created so it can install trusted extensions such as citext.
SELECT format('CREATE DATABASE %I OWNER %I', 'recruivo_demo_db', current_user)
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'recruivo_demo_db')
\gexec

SELECT format('CREATE DATABASE %I OWNER %I', 'recruivo_test', current_user)
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'recruivo_test')
\gexec
```

4.4 `docker-compose.dev.yml:41-43` — keep the `mysql:` port-forward block and add a `postgres:` block beside it. It needs its own variable: reusing `DB_FORWARD_PORT` would give both services the same published port (and MySQL's default 3306 would then be used for PostgreSQL):

```yaml
  postgres:
    ports:
      - "${POSTGRES_FORWARD_PORT:-5432}:5432"
```

4.5 `config/database.php`:

- Line 6: `'default' => env('DB_CONNECTION', 'mysql')` → `'default' => env('DB_CONNECTION', 'pgsql')`.
- Rewrite the `mysql` connection (lines 17-35) as `mysql_legacy`, reading only `LEGACY_DB_*` — with no fallback to `DB_*`, so the legacy connection can never silently point at the PostgreSQL host. Keep `driver`, `unix_socket`, `charset`, `collation`, `prefix`, `prefix_indexes`, `strict`, `engine` and the `pdo_mysql` options block exactly as they are, and drop the `'url'` key:

```php
        'mysql_legacy' => [
            'driver' => 'mysql',
            'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
            'port' => env('LEGACY_DB_PORT', '3306'),
            'database' => env('LEGACY_DB_DATABASE', ''),
            'username' => env('LEGACY_DB_USERNAME', ''),
            'password' => env('LEGACY_DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
```

- Leave the `pgsql` connection (lines 37-49) exactly as it is.

4.6 `scripts/backup.sh:37-40` — replace the database block. The dump follows `DB_CONNECTION` (read from `APP_ENV_FILE`, overridable from the environment) because MySQL remains the source of truth until the cutover flips that value: dumping the still-empty `postgres` service in that window yields a table-less artifact that passes `test -s`/`gzip -t`, is reported as success, and then has its retention prune delete the last usable MySQL backups:

```bash
DB_CONNECTION="${DB_CONNECTION:-}"
if [ -z "${DB_CONNECTION}" ] && [ -n "${APP_ENV_FILE:-}" ] && [ -f "${APP_ENV_FILE}" ]; then
    DB_CONNECTION="$(sed -n 's/^[[:space:]]*DB_CONNECTION=//p' "${APP_ENV_FILE}" | tr -d '"\r' | tail -n 1)"
fi
DB_CONNECTION="${DB_CONNECTION:-pgsql}"
echo "  - database (${DB_CONNECTION})"
case "${DB_CONNECTION}" in
    mysql*)
        compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump \
            --single-transaction --routines --triggers --events --default-character-set=utf8mb4 \
            --databases "$MYSQL_DATABASE"' | gzip -9 > "${TARGET}/database.sql.gz"
        ;;
    *)
        compose exec -T postgres sh -c 'PGPASSWORD="$DB_PASSWORD" exec pg_dump \
            --username="$DB_USERNAME" --dbname="$DB_DATABASE" --no-owner --no-acl' \
            | gzip -9 > "${TARGET}/database.sql.gz"
        ;;
esac
```

The artifact names (`database.sql.gz`, `storage.tar.gz`) and the `test -s`/`gzip -t` verification below it stay as they are, with one addition: the dump must also be shown to contain schema, since an empty one passes every existing check. `awk` reads to EOF so `set -o pipefail` cannot mistake an early exit for a failure:

```bash
zcat "${TARGET}/database.sql.gz" \
    | awk '/^(CREATE TABLE|COPY )/ { found = 1 } END { exit found ? 0 : 1 }' \
    || { echo "Database dump contains no tables - wrong DB_CONNECTION (${DB_CONNECTION})?" >&2; exit 1; }
```

Commit 2 deletes the `mysql` branch again along with the service.

4.7 `scripts/restore.sh:54-60` — replace the database block (PostgreSQL cannot drop the database it is connected to, so the drop runs against `postgres`, and it refuses to drop a database other sessions are connected to, so the first call evicts them):

```bash
echo "Restoring database (project '${PROJECT_NAME}')"
compose exec -T postgres sh -c 'PGPASSWORD="$DB_PASSWORD" exec psql \
    --username="$DB_USERNAME" --dbname="$DB_DATABASE" -v ON_ERROR_STOP=1 \
    -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = current_database() AND pid <> pg_backend_pid()"' \
    || echo "No sessions to evict (database '${DB_DATABASE:-?}' absent or unreachable)."
compose exec -T postgres sh -c 'PGPASSWORD="$DB_PASSWORD" exec psql \
    --username="$DB_USERNAME" --dbname=postgres -v ON_ERROR_STOP=1 \
    -c "DROP DATABASE IF EXISTS \"$DB_DATABASE\"" \
    -c "CREATE DATABASE \"$DB_DATABASE\""'
gunzip -c "${DATABASE_DUMP}" | compose exec -T postgres sh -c 'PGPASSWORD="$DB_PASSWORD" exec psql \
    --username="$DB_USERNAME" --dbname="$DB_DATABASE" -v ON_ERROR_STOP=1'
```

Each statement needs its own `-c`: psql wraps a single `-c` holding more than one statement in a transaction, and `DROP DATABASE` cannot run there. Do not reach for a psql `-v`/`:'db'` variable for the eviction: **psql does not interpolate variables in `-c`** (verified — it passes `:'db'` to the server verbatim and the statement fails with `syntax error at or near ":"`, while the surrounding statements still run, so the script returns 0 having skipped the eviction). Comparing against `current_database()` from a session on the target sidesteps the literal entirely; `ON_ERROR_STOP=1` is what makes a failed `-c` stop the run instead of being reported as the status of the last statement.

4.8 `.env.example`: lines 19-25 become

```
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=recruivo_db
DB_USERNAME=recruivo
DB_PASSWORD=secret
```

(`DB_ROOT_PASSWORD` is dropped — nothing reads it once the MySQL service is gone), and line 108 `# DB_HOST=mysql` → `# DB_HOST=postgres`.

4.9 Update the local `.env` (gitignored, so it is not part of any commit) so the development stack and the test suite talk to PostgreSQL: `DB_CONNECTION=pgsql`, `DB_HOST=postgres`, `DB_PORT=5432`; leave `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` as they are. `COMPOSE_PROFILES=infra` already starts the `postgres` service.

4.10 Update the now-wrong instructions: `README.md:72` (MySQL 8 prerequisite → PostgreSQL 17), `README.md:158` (`mysql_data` → `postgres_data`), and in `docs/docker.md` the architecture line (12-13), the service list (22), the local port-forward line (41), the interpolation examples (69-70, 79-80), the data-ownership paragraph (156), the restore description (193-195), and the log-rotation note (278). `docs/worklog.md` and `docs/audit-search-contract.md` are historical records and stay untouched, as do the `MySQL` strings in `database/seeders/UserSeeder.php:165,285` (demo profile skills) and `resources/lang/{en,fr}/profile.php:91` (a "PostgreSQL" placeholder already).

### 5. Add the one-way data copy command

Create `app/Console/Commands/ImportFromMySql.php`. It is only ever executed during the cutover, from a throwaway container, against a freshly migrated PostgreSQL database.

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportFromMySql extends Command
{
    protected $signature = 'db:import-from-mysql
                            {--source=mysql_legacy : connection to read from}
                            {--target=pgsql : connection to write to}
                            {--chunk=500 : rows per insert statement}';

    protected $description = 'Copy every application table from the legacy MySQL connection into PostgreSQL';

    /**
     * Foreign-key-safe order: each table references only tables listed before it.
     *
     * @var list<string>
     */
    private const TABLES = [
        'companies',
        'users',
        'password_reset_tokens',
        'failed_jobs',
        'personal_access_tokens',
        'jobs',
        'candidate_profiles',
        'applications',
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'queue_jobs',
        'notifications',
        'posts',
        'saved_jobs',
        'application_status_events',
        'recruiter_note_templates',
    ];

    public function handle(): int
    {
        $source = (string) $this->option('source');
        $target = (string) $this->option('target');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($source === $target) {
            $this->error('Source and target connections are the same.');

            return self::FAILURE;
        }

        $occupied = array_values(array_filter(
            self::TABLES,
            fn (string $table): bool => Schema::connection($target)->hasTable($table)
                && DB::connection($target)->table($table)->exists()
        ));

        if ($occupied !== []) {
            $this->error('Target already holds rows in: '.implode(', ', $occupied));
            $this->error('Point this command at a freshly migrated database, or wipe the target first.');

            return self::FAILURE;
        }

        $summary = [];

        foreach (self::TABLES as $table) {
            if (! Schema::connection($source)->hasTable($table)) {
                $this->warn("Skipped {$table}: absent from the source database.");

                continue;
            }

            if (! Schema::connection($target)->hasTable($table)) {
                $this->error("Target is missing the {$table} table; run 'php artisan migrate --force' first.");

                return self::FAILURE;
            }

            $columns = collect(Schema::connection($target)->getColumns($table))->keyBy('name');

            $rows = DB::connection($source)->table($table)
                ->when(
                    Schema::connection($source)->hasColumn($table, 'id'),
                    fn ($query) => $query->orderBy('id')
                )
                ->get();

            $payload = $rows->map(fn (object $row): array => $this->cast((array) $row, $columns))->all();

            foreach (array_chunk($payload, $chunk) as $batch) {
                DB::connection($target)->table($table)->insert($batch);
            }

            $expected = DB::connection($source)->table($table)->count();
            $copied = DB::connection($target)->table($table)->count();
            $ok = $expected === $copied;

            $summary[] = [$table, $expected, $copied, $ok ? 'ok' : 'MISMATCH'];

            if (! $ok) {
                $this->error("Row count mismatch for {$table}: source {$expected}, target {$copied}");
            }

            $this->resetSequence($target, $table, $columns);
        }

        $this->newLine();
        $this->table(['table', 'source', 'target', 'status'], $summary);

        $mismatched = collect($summary)->contains(fn (array $row): bool => $row[3] !== 'ok');

        $this->info($mismatched ? 'Import finished with mismatches.' : 'Import complete.');

        return $mismatched ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<int, array{name: string, type_name: string, auto_increment: bool}>  $columns
     * @return array<string, mixed>
     */
    private function cast(array $row, Collection $columns): array
    {
        $values = [];

        foreach ($row as $name => $value) {
            if (! $columns->has($name)) {
                continue;
            }

            if ($value === null) {
                $values[$name] = null;

                continue;
            }

            $values[$name] = match (true) {
                // MySQL stores booleans as tinyint(1); PostgreSQL rejects the integer.
                $columns->get($name)['type_name'] === 'bool' => (bool) $value,
                // json/jsonb take the stored document verbatim.
                in_array($columns->get($name)['type_name'], ['json', 'jsonb'], true) => is_string($value)
                    ? $value
                    : json_encode($value),
                default => $value,
            };
        }

        return $values;
    }

    /**
     * Rows are inserted with their original ids, which leaves each sequence
     * behind; the next insert would collide with an existing primary key.
     *
     * @param  Collection<int, array{name: string, type_name: string, auto_increment: bool}>  $columns
     */
    private function resetSequence(string $connection, string $table, Collection $columns): void
    {
        $id = $columns->get('id');

        if ($id === null || ! $id['auto_increment']) {
            return;
        }

        $max = DB::connection($connection)->table($table)->max('id');

        if ($max === null) {
            return;
        }

        DB::connection($connection)->statement(
            'select setval(pg_get_serial_sequence(?, ?), ?)',
            [$table, 'id', (int) $max]
        );
    }
}
```

The command exits non-zero on any row-count mismatch, which is what the cutover gates on. Three behaviours beyond that shape were added after review, all verified against a real MySQL→PostgreSQL copy; `app/Console/Commands/ImportFromMySql.php` is the source of truth for the listing above:

- **`roles` is excluded from the empty-target guard** (`MIGRATION_SEEDED`) and its rows are matched on `guard_name`+`name` (`withoutExistingRoles`), because `2026_09_10_200000_ensure_default_roles_exist` already created them: the guard would otherwise refuse every freshly migrated target, and an unfiltered insert would die on `roles_pkey`.
- **A source column absent from the target aborts the run.** `cast()` skips unknown columns, which the row-count comparison cannot detect, so the copy would report `ok` while silently dropping the values (`array_diff` on the source column listing, before any insert).
- **Target sequence state is read from a distilled `['type' => ..., 'auto' => ...]` map** rather than the raw `getColumns()` arrays, which is what PHPStan accepts as a `Collection` value type.

Verified: 19/19 tables `ok`, equal counts, FK order enforced by PostgreSQL itself, every sequence advanced to `max(id)`, and a second run refused with `Target already holds rows in: …`.

### 6. Commit 1 and deploy

Commit every change from steps 1-5 as one commit (`feat(db): run on PostgreSQL`) and push to `main`. Wait for CI: the backend job now proves that all 40 migrations build a PostgreSQL schema and that the full test suite (364 tests) passes against PostgreSQL. The deploy jobs then recreate the production and demo containers **with their unchanged env files**, so both keep serving from MySQL; the `postgres` container starts alongside with an empty volume.

If the deploy fails, nothing has changed for users — investigate and re-run before touching the cutover.

### 7. Cutover on the deploy box

Run every command on the deploy host (`ssh deploy`). Nothing here needs a repository checkout.

7.1 Final MySQL backup of both databases, plus a verification of the artifact:

```bash
mkdir -p /mnt/hdd2-data/backups
docker exec recruivo-mysql-1 sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump \
    --single-transaction --routines --triggers --events --default-character-set=utf8mb4 \
    --databases recruivo_db recruivo_demo_db' \
    | gzip -9 > /mnt/hdd2-data/backups/mysql-final-$(date -u +%Y%m%dT%H%M%SZ).sql.gz
ls -lh /mnt/hdd2-data/backups/mysql-final-*.sql.gz
gzip -t /mnt/hdd2-data/backups/mysql-final-*.sql.gz && echo "gzip ok"
zcat /mnt/hdd2-data/backups/mysql-final-*.sql.gz | grep -c 'CREATE TABLE'
```

Expect ~38 tables (19 × 2 databases). Keep this file for the whole rollback window. Uploads are not affected by the migration (`app_storage` is untouched).

Record the exact source counts used later as the comparison baseline:

```bash
docker exec recruivo-mysql-1 sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -N -e "
SELECT (SELECT COUNT(*) FROM recruivo_db.users)      AS users,
       (SELECT COUNT(*) FROM recruivo_db.companies)  AS companies,
       (SELECT COUNT(*) FROM recruivo_db.jobs)       AS jobs,
       (SELECT COUNT(*) FROM recruivo_db.applications) AS applications,
       (SELECT COUNT(*) FROM recruivo_db.candidate_profiles) AS profiles,
       (SELECT COUNT(*) FROM recruivo_db.saved_jobs) AS saved_jobs,
       (SELECT COUNT(*) FROM recruivo_db.posts)      AS posts,
       (SELECT COUNT(*) FROM recruivo_db.model_has_roles) AS role_grants;"'
```

7.2 Write a purpose-built env file for the throwaway container of each environment. It is never printed and never sourced by a running service; the values are copied from the deployed env file without echoing them:

```bash
for P in recruivo recruivo-demo; do
  ( set -a; . "/mnt/hdd2-data/containers/$P/.env"; set +a
    umask 077
    cat > "/mnt/hdd2-data/containers/$P/.env.cutover" <<EOF
APP_KEY=$APP_KEY
APP_ENV=$APP_ENV
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=$DB_DATABASE
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD
LEGACY_DB_HOST=mysql
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=$DB_DATABASE
LEGACY_DB_USERNAME=$DB_USERNAME
LEGACY_DB_PASSWORD=$DB_PASSWORD
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
EOF
  )
done
ls -l /mnt/hdd2-data/containers/*/.env.cutover
```

7.3 Build the PostgreSQL schema in both databases, from the currently deployed image (the tag is the revision the running container records):

```bash
SHA=$(docker inspect --format '{{index .Config.Labels "org.opencontainers.image.revision"}}' recruivo-app-1)
echo "image tag: sha-$SHA"
for P in recruivo recruivo-demo; do
  docker run --rm --network recruivo_network \
    --env-file "/mnt/hdd2-data/containers/$P/.env.cutover" \
    "ghcr.io/mouadlotfi/recruivo:sha-$SHA" php artisan migrate --force --no-interaction
done
```

Expect `Nothing to migrate` / a list of applied migrations ending without errors, and `citext` created. Confirm the extension exists in both databases:

```bash
for D in recruivo_db recruivo_demo_db; do
  docker exec recruivo-postgres-1 psql -U recruivo -d "$D" -t -A -c "select extname from pg_extension order by 1;"
done
```

7.4 Copy the production rows. The demo database is deliberately not copied: the demo deploy job runs `php artisan demo:reset --force` after every deployment, which rebuilds it from the seeders on PostgreSQL — copying it first would be undone seconds later.

```bash
docker run --rm --network recruivo_network \
  --env-file /mnt/hdd2-data/containers/recruivo/.env.cutover \
  "ghcr.io/mouadlotfi/recruivo:sha-$SHA" php artisan db:import-from-mysql
```

The command prints a per-table source/target table and exits 0 only if every count matches. Compare its `source` column against the counts recorded in 7.1.

7.5 Flip the two deployed env files. The window between 7.4 and this step is the only period in which a write can be lost, so keep it short:

```bash
for P in recruivo recruivo-demo; do
  F="/mnt/hdd2-data/containers/$P/.env"
  cp -p "$F" "$F.bak-prepostgres"
  # The rename of the `mysql` connection is not a safety net: Laravel merges its
  # own default `mysql` connection (reading DB_*), so an env file that loses or
  # never had DB_CONNECTION would run a MySQL client against the PostgreSQL host
  # and hang instead of failing. Assert the key exists before flipping.
  grep -q '^DB_CONNECTION=' "$F" || { echo "::error::$F has no DB_CONNECTION"; exit 1; }
  sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/; s/^DB_HOST=.*/DB_HOST=postgres/; s/^DB_PORT=.*/DB_PORT=5432/' "$F"
  grep -E '^DB_CONNECTION=|^DB_HOST=|^DB_PORT=' "$F"
done
```

Expect `DB_CONNECTION=pgsql`, `DB_HOST=postgres`, `DB_PORT=5432` for both. Remove the cutover env files now that they are no longer needed:

```bash
rm -f /mnt/hdd2-data/containers/*/.env.cutover
```

7.6 Deploy with the gated pipeline so the health check and the automatic image rollback stay in force:

```bash
gh workflow run ci.yml --ref main     # from the workstation
gh run watch
```

The production deploy recreates the app/queue/scheduler/migrate containers against PostgreSQL and must report `Deploy (Production)` success (health 200, which itself checks the database connection, migrations, cache, app key and storage). The demo job then rebuilds the demo dataset on PostgreSQL.

### 8. Commit 2: retire the MySQL service

Only after the cutover is verified. Commit `chore(db): remove the MySQL service`:

- `docker-compose.yml`: delete the `mysql` service (lines 57-80), the `mysql:` entry in the `x-laravel-service` `depends_on` block (lines 34-37), the `mysql_data:` volume (line 125), and the `mysql_data` mention in the comment at line 10 (→ `postgres_data`).
- `docker-compose.dev.yml`: delete the `mysql:` port-forward block (lines 41-43).
- `.env.example`: nothing further (already done in 4.8).

Deploy it. `docker compose up -d --remove-orphans` stops and removes the MySQL container; the named volume `recruivo_mysql_data` is **not** deleted by compose and must not be deleted while the rollback path is wanted. Verify it survives:

```bash
docker volume ls --format '{{.Name}}' | grep mysql
```

## Critical files & anchors

- `docker-compose.yml` — the `mysql`/`postgres` service definitions, the `x-laravel-service` `depends_on` and `DB_HOST` default, and the `volumes:` section. Everything about the infrastructure swap lands here.
- `config/database.php` — the `mysql` → `mysql_legacy` connection rename and the `pgsql` default. Note the rename does **not** make a leftover `mysql` reference fail: Laravel merges its own framework defaults into `database.connections` (`LoadConfiguration::mergeableOptions`), so `mysql` still resolves at runtime — to the framework default, which reads `DB_HOST`/`DB_DATABASE`/... A stale `DB_CONNECTION=mysql` after the cutover therefore builds a MySQL client aimed at the PostgreSQL host and hangs rather than erroring, which is why the cutover asserts `DB_CONNECTION=pgsql` rather than trusting the rename.
- `app/Console/Commands/ImportFromMySql.php` — the only data-carrying step; the table order and the boolean cast are the two things that break the copy if changed.
- `.github/workflows/ci.yml` — `backend` job (service container, extensions, timeout) and the two deploy jobs' gates, which stay untouched.
- `app/Http/Controllers/PostController.php:63-66` — the single MySQL-only query in the application.

## Verification

Each check has a concrete input and observable output. Run them in this order.

1. **The whole suite on PostgreSQL, locally, before pushing** — after 4.9 the local stack runs PostgreSQL:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d postgres
   docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm -T app php artisan test --compact
   ```
   Expect the full suite green (361 tests) against `recruivo_test`, which proves the migrations build on PostgreSQL, `whereLike` matches case-insensitively, `slug->>` resolves, and the mixed-case login test passes. Any failure here is a real dialect problem — fix it before pushing.
2. **PostgreSQL schema builds** — CI `backend` job on the pushed commit: `Backend tests + lint` green. This runs all migrations plus the whole suite against the PostgreSQL service container, and the new mixed-case login test in `tests/Feature/Auth/LoginTest.php` is the assertion that `citext` is in force.
3. **`PostController` on PostgreSQL** — after the cutover, on the demo site (production has no posts): `curl -s -o /dev/null -w '%{http_code}' https://demo.recruivo.work/en/posts/<slug>` → 200, and the page renders the title. A slug is available from the demo database: `docker exec recruivo-postgres-1 psql -U recruivo -d recruivo_demo_db -t -A -c "select slug->>'en' from posts limit 1;"`. Before the fix this returned 500/404 on PostgreSQL.
4. **Data copy** — the `db:import-from-mysql` output table (step 7.4) must show `ok` for all 19 tables, with `source` counts equal to the 7.1 baseline. Then confirm independently on PostgreSQL:
   ```bash
   docker exec recruivo-postgres-1 psql -U recruivo -d recruivo_db -t -A -F' ' -c "SELECT (SELECT COUNT(*) FROM users), (SELECT COUNT(*) FROM jobs), (SELECT COUNT(*) FROM companies), (SELECT COUNT(*) FROM applications), (SELECT COUNT(*) FROM candidate_profiles), (SELECT COUNT(*) FROM saved_jobs), (SELECT COUNT(*) FROM posts), (SELECT COUNT(*) FROM model_has_roles);"
   ```
   → identical numbers to the MySQL baseline from 7.1.
5. **Sequences were reset** — `docker exec recruivo-postgres-1 psql -U recruivo -d recruivo_db -t -A -c "select last_value, is_called from users_id_seq;"` → `last_value` equals 1 (the only user's id). A missing reset shows up as a duplicate-key error on the first insert.
6. **Production serves from PostgreSQL** — `curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8085/api/health` → 200, and `curl -s http://127.0.0.1:8085/api/health | jq -r '.checks'` → every check `ok`. Then anonymously load `https://recruivo.work/en`, `/en/jobs`, `/en/companies`, `/en/search?search=laravel` and confirm 200 plus rendered results (the search check exercises `ILIKE`).
7. **Demo rebuilt** — `https://demo.recruivo.work/en` returns 200 and `/en/jobs` lists the seeded jobs, proving `demo:reset` runs on PostgreSQL.
8. **MySQL is idle, volume intact** — after commit 2: `docker ps -a --format '{{.Names}}' | grep mysql` → nothing, and `docker volume ls --format '{{.Name}}' | grep mysql` → `recruivo_mysql_data` still listed. `docker compose --env-file /mnt/hdd2-data/containers/recruivo/.env -p recruivo config` (run from the runner checkout) parses without error.

## Assumptions & contingencies

- **The role is the database superuser.** This keeps `CREATE EXTENSION citext`, `CREATE DATABASE` (needed by `--parallel`) and `migrate:fresh` working with no grants. If `create extension citext` ever fails, fall back to `lower(email)` unique indexes plus lowercased writes and lookups — a larger change that this plan avoids on purpose.
- **The deployed env files must set `DB_CONNECTION` explicitly.** Production keeps serving MySQL after commit 1 because those files still say `DB_CONNECTION=mysql`, not because of a config default. An env file that omits the key now gets the `pgsql` default while `DB_HOST` may still be `mysql`, and `7.5`'s `sed` only rewrites the key, it does not add it — so confirm `DB_CONNECTION` is present (or add it) before deploying commit 1.
- **The cutover window must be quiet.** The application keeps serving from MySQL until 7.5 flips the env files, so registrations or applications created between 7.4 and 7.5 exist only in MySQL. Run 7.2-7.5 back to back; if the site took writes in that window, recover them by re-running 7.3 with `migrate:fresh --force` against `recruivo_db` and then 7.4, or accept that the affected rows are absent (production held a single account at planning time).
- **Rollback before commit 2.** Restore the env file (`cp -p /mnt/hdd2-data/containers/<project>/.env.bak-prepostgres /mnt/hdd2-data/containers/<project>/.env`), then repeat 7.6. MySQL is still running with all of its data, and the image still contains `pdo_mysql`. Data written to PostgreSQL in the meantime is lost.
- **Rollback after commit 2.** Revert that commit and push; the deploy restarts MySQL from `recruivo_mysql_data` (never delete that volume), then run the previous step.
- **Memory pressure.** The box has ~1 GiB free with swap almost fully used, and MySQL and PostgreSQL run side by side during the cutover. If a container is OOM-killed, run `docker stop recruivo-mysql-1` to free it — the data stays in the volume and `docker start recruivo-mysql-1` brings it back.
- **`pdo_mysql` and the `mysql_legacy` connection stay** for as long as the rollback path is wanted; both become dead weight and can be removed in the same change that deletes the `recruivo_mysql_data` volume.
- **`DB_ROOT_PASSWORD` stays in the deployed env files** (unused after commit 2) because the MySQL container's root password is derived from it: removing the key now would change that password on any restart and break the rollback path.
