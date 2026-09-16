<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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

    /**
     * Tables a migration already fills, so a freshly migrated target is not
     * empty: the pre-flight check skips them and the import adds only the rows
     * they are still missing.
     *
     * @var list<string>
     */
    private const MIGRATION_SEEDED = [
        'roles',
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
            fn (string $table): bool => ! in_array($table, self::MIGRATION_SEEDED, true)
                && Schema::connection($target)->hasTable($table)
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

            // Only the two facts the copy needs: the target's type (to cast
            // MySQL's tinyint booleans) and whether id is sequence-backed.
            $columns = collect(Schema::connection($target)->getColumns($table))
                ->mapWithKeys(fn (array $column): array => [
                    $column['name'] => ['type' => $column['type_name'], 'auto' => $column['auto_increment']],
                ])
                ->all();

            // A source column the target lacks would be dropped by cast() and
            // the row counts would still match, so the one check that claims to
            // guarantee no loss would report success. Refuse instead.
            $dropped = array_diff(Schema::connection($source)->getColumnListing($table), array_keys($columns));

            if ($dropped !== []) {
                $this->error("{$table}: source columns absent from the target: ".implode(', ', $dropped));
                $this->error('The target schema is behind the source; migrate it before copying.');

                return self::FAILURE;
            }

            $rows = DB::connection($source)->table($table)
                ->when(
                    Schema::connection($source)->hasColumn($table, 'id'),
                    fn ($query) => $query->orderBy('id')
                )
                ->get();

            $payload = $rows->map(fn (object $row): array => $this->cast((array) $row, $columns))->all();

            if (in_array($table, self::MIGRATION_SEEDED, true)) {
                $payload = $this->withoutExistingRoles($target, $payload);
            }

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
     * @param  array<string, array{type: string, auto: bool}>  $columns
     * @return array<string, mixed>
     */
    private function cast(array $row, array $columns): array
    {
        $values = [];

        foreach ($row as $name => $value) {
            if (! array_key_exists($name, $columns)) {
                continue;
            }

            if ($value === null) {
                $values[$name] = null;

                continue;
            }

            $values[$name] = match (true) {
                // MySQL stores booleans as tinyint(1); PostgreSQL rejects the integer.
                $columns[$name]['type'] === 'bool' => (bool) $value,
                // json/jsonb take the stored document verbatim.
                in_array($columns[$name]['type'], ['json', 'jsonb'], true) => is_string($value)
                    ? $value
                    : json_encode($value),
                default => $value,
            };
        }

        return $values;
    }

    /**
     * Reference rows are matched on their natural key, so the ones a migration
     * already created are not inserted a second time under a taken primary key.
     * A source row that differs from the migrated one is still copied, and
     * collides loudly rather than being dropped silently.
     *
     * @param  list<array<string, mixed>>  $payload
     * @return list<array<string, mixed>>
     */
    private function withoutExistingRoles(string $connection, array $payload): array
    {
        $existing = DB::connection($connection)->table('roles')
            ->get(['guard_name', 'name'])
            ->map(fn (object $role): string => $role->guard_name.'|'.$role->name)
            ->all();

        return array_values(array_filter(
            $payload,
            fn (array $row): bool => ! in_array($row['guard_name'].'|'.$row['name'], $existing, true)
        ));
    }

    /**
     * Rows are inserted with their original ids, which leaves each sequence
     * behind; the next insert would collide with an existing primary key.
     *
     * @param  array<string, array{type: string, auto: bool}>  $columns
     */
    private function resetSequence(string $connection, string $table, array $columns): void
    {
        if (! ($columns['id']['auto'] ?? false)) {
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
