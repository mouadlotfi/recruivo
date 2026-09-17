<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule as ScheduleFacade;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Tests\TestCase;

/**
 * A backup is the only copy of the platform's data once the original is gone, so
 * the things that decide whether one happens at all - the schedule and its two
 * off switches - are pinned here rather than left to be noticed in production.
 */
class BackupTest extends TestCase
{
    /**
     * Re-run routes/console.php and return the events it schedules.
     *
     * The console kernel loads the route file inside createApplication(), before
     * any test method runs, so a config or environment change made in a test has
     * no effect on the schedule that is already registered. Swapping in a fresh
     * Schedule and re-requiring the file records what the real rules produce,
     * rather than asserting on a copy of them.
     *
     * @return array<int, Event>
     */
    private function scheduledEvents(): array
    {
        $schedule = new Schedule;
        ScheduleFacade::swap($schedule);

        require base_path('routes/console.php');

        return $schedule->events();
    }

    /**
     * The artisan command each scheduled event runs.
     *
     * @return array<int, string>
     */
    private function scheduledCommands(): array
    {
        return collect($this->scheduledEvents())
            ->map(function (Event $event) {
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

    public function test_every_scheduled_command_writes_a_log(): void
    {
        // A scheduled command's output is redirected to /dev/null unless the event
        // says otherwise, and that redirect carries stderr with it - so a command
        // which exits non-zero leaves nothing behind, not even its error. A backup
        // failed that way and went unnoticed, which is what this guards.
        //
        // Commands only: the schedule also carries one closure, which dispatches the
        // queue heartbeat rather than running anything, and whose silence is exactly
        // what queue:health exists to notice.
        $commands = collect($this->scheduledEvents())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'artisan'));

        $this->assertNotEmpty($commands);

        foreach ($commands as $event) {
            $this->assertNotSame(
                $event->getDefaultOutput(),
                $event->output,
                "The [{$event->command}] event writes its output nowhere, so a failure would be silent."
            );
        }
    }

    public function test_the_backup_commands_share_one_log(): void
    {
        // The failure email sends whatever file its event points at, so sharing one
        // is the point: a backup alert should carry the backup history with it.
        $backups = collect($this->scheduledEvents())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'backup:'));

        $this->assertCount(3, $backups);

        foreach ($backups as $event) {
            $this->assertSame(storage_path('logs/schedule.log'), $event->output);
        }
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

    public function test_the_backups_disk_writes_archives_the_deploy_host_can_read(): void
    {
        // Archives land in a `<backup name>/` subdirectory, and Flysystem creates
        // directories 0700 by default. The directory then belongs to the
        // container's www-data, and the host's operator - a different user with a
        // different group - cannot list a single archive without sudo. The mode
        // on disk is what decides that, so this writes for real rather than
        // asserting the config that is meant to produce it.
        $root = storage_path('framework/testing/backups-disk');

        File::deleteDirectory($root);
        Config::set('filesystems.disks.backups.root', $root);
        Storage::forgetDisk('backups');

        $disk = Storage::disk('backups');
        $disk->put('Recruivo/archive.zip', 'contents');

        $directory = dirname($disk->path('Recruivo/archive.zip'));
        $archive = $disk->path('Recruivo/archive.zip');
        clearstatcache();

        // The 0005 / 0004 bits are the ones the operator outside www-data needs.
        $this->assertSame(0005, fileperms($directory) & 0005, 'The archive directory is not traversable by the host operator.');
        $this->assertSame(0004, fileperms($archive) & 0004, 'The archive is not readable by the host operator.');

        File::deleteDirectory($root);
    }

    public function test_the_default_connection_is_postgresql(): void
    {
        // This is what decides both where the application connects and which
        // database spatie dumps. Laravel merges the framework's own `mysql`
        // connection back in whatever this application does, so its absence cannot
        // be asserted here - the default is the thing that matters.
        $this->assertSame('pgsql', config('database.default'));
    }

    public function test_the_postgresql_connection_is_fully_defined(): void
    {
        // config/database.php redefines `pgsql`, which Laravel otherwise merges in
        // from the framework with a shallow array_merge. A partial definition would
        // replace the framework's outright and leave the connection without a
        // driver - which is how production would break, silently, on deploy.
        $connection = config('database.connections.pgsql');

        foreach (['driver', 'host', 'port', 'database', 'username', 'password', 'charset', 'prefix', 'search_path'] as $key) {
            $this->assertArrayHasKey($key, $connection, "The pgsql connection lost its [{$key}] key.");
        }
    }

    public function test_the_backup_dumps_the_default_connection(): void
    {
        // spatie dumps whatever connections this list names. A stray DB_CONNECTION
        // would point the dump at a service that no longer exists, and nothing but
        // PostgreSQL runs now, so there is no fallback to land on.
        $this->assertSame([config('database.default')], config('backup.backup.source.databases'));
    }

    public function test_every_notification_spatie_can_raise_has_an_entry(): void
    {
        // spatie resolves a notification's channels by indexing this map with the
        // notification's own class name, so a missing key is an undefined-array-key
        // exception, not a silent no-op. Leaving the successes out took
        // backup:monitor down on a healthy morning, and would have taken backup:run
        // down on the nights it worked - breaking the backups in order to add
        // alerting to them.
        $map = (require base_path('config/backup.php'))['notifications']['notifications'];

        foreach ([
            BackupHasFailedNotification::class,
            BackupWasSuccessfulNotification::class,
            CleanupHasFailedNotification::class,
            CleanupWasSuccessfulNotification::class,
            UnhealthyBackupWasFoundNotification::class,
            HealthyBackupWasFoundNotification::class,
        ] as $notification) {
            $this->assertArrayHasKey(
                $notification,
                $map,
                "spatie raises [{$notification}], so reaching it without an entry here throws."
            );
        }
    }

    public function test_backup_failures_are_emailed_to_the_configured_recipient(): void
    {
        // The config computes this from the environment every time it loads, so it
        // is re-required here rather than read from the copy loaded at boot.
        putenv('BACKUP_NOTIFICATION_MAIL_TO=alerts@example.com');

        try {
            $notifications = (require base_path('config/backup.php'))['notifications'];
        } finally {
            putenv('BACKUP_NOTIFICATION_MAIL_TO');
        }

        $map = $notifications['notifications'];

        $this->assertSame(['mail'], $map[BackupHasFailedNotification::class]);
        $this->assertSame(['mail'], $map[UnhealthyBackupWasFoundNotification::class]);
        $this->assertSame(['mail'], $map[CleanupHasFailedNotification::class]);

        // A nightly "it worked" is noise, and a backup that stopped running
        // altogether arrives as UnhealthyBackupWasFound instead - so the successes
        // are listed with no channel.
        $this->assertSame([], $map[BackupWasSuccessfulNotification::class]);
        $this->assertSame([], $map[HealthyBackupWasFoundNotification::class]);
        $this->assertSame([], $map[CleanupWasSuccessfulNotification::class]);

        $this->assertSame('alerts@example.com', $notifications['mail']['to']);
    }

    public function test_a_missing_alert_recipient_disables_the_alerts_not_the_backup(): void
    {
        // spatie builds its config on every backup command and throws when the
        // recipient is not a valid address. An unset variable must therefore leave
        // a real address behind and take the channels away - the other way round
        // would break backups in order to silence their alerts.
        putenv('BACKUP_NOTIFICATION_MAIL_TO');

        try {
            $notifications = (require base_path('config/backup.php'))['notifications'];
        } finally {
            putenv('BACKUP_NOTIFICATION_MAIL_TO');
        }

        // Every entry stays. Emptying the map would break each command that raises
        // a notification, which is most of them.
        $this->assertCount(6, $notifications['notifications']);

        foreach ($notifications['notifications'] as $class => $channels) {
            $this->assertSame([], $channels, "[{$class}] would still be sent with no recipient configured.");
        }

        $this->assertNotFalse(
            filter_var($notifications['mail']['to'], FILTER_VALIDATE_EMAIL),
            'An unvalidatable recipient would make spatie throw on every backup command.'
        );
    }
}
