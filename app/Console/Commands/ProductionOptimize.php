<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProductionOptimize extends Command
{
    protected $signature = 'production:optimize';
    protected $description = 'Optimize the application for production';

    public function handle()
    {
        $this->info('Optimizing application for production...');

        // Clear all caches first
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('event:clear');

        // Optimize for production
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
        $this->call('event:cache');

        // Optimize autoloader
        $this->call('optimize');

        $this->info('Application optimized for production!');
    }
}
