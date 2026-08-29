<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        if (config('app.demo_scheduled_reset') || app()->environment('demo')) {
            $schedule->command('demo:reset --force')
                ->dailyAt('03:00')
                ->withoutOverlapping();
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
