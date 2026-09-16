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
        ->withoutOverlapping();
}

// Backups. Skipped in the Demo environment, whose database demo:reset rebuilds
// nightly. Order matters: clean prunes first, run takes the backup, and monitor
// judges it afterwards - it fails when the newest backup is older than a day,
// which is the only thing that notices a backup that silently stopped running.
if (config('backup.backup.enabled') && ! app()->environment('demo')) {
    Schedule::command('backup:clean')->dailyAt('01:00');
    Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping();
    Schedule::command('backup:monitor')->dailyAt('03:00');
}
