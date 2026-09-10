<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled tasks must be registered here: this application boots through
// `Application::configure()`, so App\Console\Kernel (and its schedule() method)
// is never resolved.
if (config('app.demo_scheduled_reset') || app()->environment('demo')) {
    Schedule::command('demo:reset --force')
        ->dailyAt('03:00')
        ->withoutOverlapping();
}
