<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled tasks belong here: App\Console\Kernel is never resolved under
// `Application::configure()`.
if (config('app.demo_scheduled_reset') || app()->environment('demo')) {
    Schedule::command('demo:reset --force')
        ->dailyAt('03:00')
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/schedule.log'));
}

// Backups. Skipped in the Demo environment, whose database demo:reset rebuilds
// nightly. Order matters: clean prunes first, run takes the backup, and monitor
// judges it afterwards - it fails when the newest backup is older than a day,
// which is the only thing that notices a backup that silently stopped running.
//
// Every one of them writes to the same log, and so does demo:reset. A scheduled
// command's output is redirected to /dev/null unless the event says otherwise,
// and the redirect takes stderr with it. Laravel still records that a scheduled
// command failed, and spatie records its own reasons, but the underlying
// process's own error - the one line from the dump tool saying what was actually
// wrong - only ever existed on that discarded stream. The first failed backup
// here could be seen to have failed, in the log, without saying why.
//
// Being told is the other half and it is not this file's job: failures are
// emailed to BACKUP_NOTIFICATION_MAIL_TO, which is what turns a line in a log
// into something someone notices.
if (config('backup.backup.enabled') && ! app()->environment('demo')) {
    $scheduleLog = storage_path('logs/schedule.log');

    Schedule::command('backup:clean')->dailyAt('01:00')->appendOutputTo($scheduleLog);
    Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping()->appendOutputTo($scheduleLog);
    Schedule::command('backup:monitor')->dailyAt('03:00')->appendOutputTo($scheduleLog);
}
