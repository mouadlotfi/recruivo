<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schedule as ScheduleFacade;
use Tests\TestCase;

/**
 * A backup is the only copy of the platform's data once the original is gone, so
 * the things that decide whether one happens at all - the schedule and its two
 * off switches - are pinned here rather than left to be noticed in production.
 */
class BackupTest extends TestCase
{
    /**
     * Re-run routes/console.php and return the Artisan commands it schedules.
     *
     * The console kernel loads the route file inside createApplication(), before
     * any test method runs, so a config or environment change made in a test has
     * no effect on the schedule that is already registered. Swapping in a fresh
     * Schedule and re-requiring the file records what the real rules produce,
     * rather than asserting on a copy of them.
     *
     * @return array<int, string>
     */
    private function scheduledCommands(): array
    {
        $schedule = new Schedule;
        ScheduleFacade::swap($schedule);

        require base_path('routes/console.php');

        return collect($schedule->events())
            ->map(function ($event) {
                // Events hold the whole invocation, e.g.
                // "'/usr/local/bin/php' 'artisan' backup:run".
                $parts = is_string($event->command) ? explode(' ', $event->command) : [];

                return $parts[2] ?? null;
            })
            ->filter()
            ->values()
            ->all();
    }

    public function test_the_backup_commands_are_scheduled(): void
    {
        $commands = $this->scheduledCommands();

        $this->assertContains('backup:clean', $commands);
        $this->assertContains('backup:run', $commands);
        $this->assertContains('backup:monitor', $commands);
    }

    public function test_the_backup_commands_are_not_scheduled_when_backups_are_disabled(): void
    {
        Config::set('backup.backup.enabled', false);

        $this->assertNotContains('backup:run', $this->scheduledCommands());
    }

    public function test_the_backup_commands_are_not_scheduled_in_the_demo_environment(): void
    {
        // The Demo environment is rebuilt by demo:reset every night, so a backup
        // of it is waste. Guarded twice: on the environment and on the flag.
        App::detectEnvironment(fn () => 'demo');

        $commands = $this->scheduledCommands();

        $this->assertNotContains('backup:run', $commands);
        $this->assertContains('demo:reset', $commands);
    }

    public function test_the_backup_covers_uploads_and_not_the_codebase(): void
    {
        $include = config('backup.backup.source.files.include');

        // The package ships [base_path()] by default, which would put app/,
        // config/ and public/build into every archive.
        $this->assertNotContains(base_path(), $include);

        foreach ($include as $path) {
            $this->assertTrue(
                str_starts_with($path, storage_path()) || $path === env('BACKUP_ENV_FILE'),
                "The backup includes an unexpected path: {$path}"
            );
        }
    }

    public function test_backups_are_written_to_the_backups_disk(): void
    {
        $this->assertSame(['backups'], config('backup.backup.destination.disks'));
        $this->assertSame('local', config('filesystems.disks.backups.driver'));
        $this->assertNotEmpty(config('filesystems.disks.backups.root'));
    }

    public function test_the_mysql_connection_is_fully_defined(): void
    {
        // config/database.php redefines `mysql`, which Laravel otherwise merges in
        // from the framework with a shallow array_merge. A partial definition would
        // replace the framework's outright and leave the connection without a
        // driver - which is how production would break, silently, on deploy.
        $connection = config('database.connections.mysql');

        foreach (['driver', 'host', 'port', 'database', 'username', 'password', 'charset', 'collation', 'prefix', 'strict'] as $key) {
            $this->assertArrayHasKey($key, $connection, "The mysql connection lost its [{$key}] key.");
        }
    }

    public function test_the_mysql_connection_skips_ssl_when_dumping(): void
    {
        // MySQL is the rollback path after the PostgreSQL cutover, and its backup
        // must still work. Debian ships MariaDB's mysqldump, which verifies the
        // server's self-signed certificate and aborts - and it rejects the
        // `ssl-mode=DISABLED` spelling that `skip_ssl` alone emits, because that
        // is MySQL's syntax. So the MariaDB spelling is pinned too.
        $dump = config('database.connections.mysql.dump');

        $this->assertTrue($dump['skip_ssl']);
        $this->assertSame('skip-ssl', $dump['ssl_flag']);
    }
}
