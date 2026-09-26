<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DemoReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely reset the demo environment to the canonical seeded state';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Layer 1: Strict Production Guard
        if (app()->environment('production') || config('app.env') === 'production' || app()->isProduction()) {
            $this->error('CRITICAL ERROR: demo:reset cannot and will never run in the production environment.');
            Log::alert('Blocked attempt to run demo:reset in production environment.');

            return self::FAILURE;
        }

        // Layer 2: Allowed Environments Allowlist
        $allowedEnvironments = ['demo', 'local', 'development', 'testing'];
        if (! in_array(app()->environment(), $allowedEnvironments, true) && ! config('app.is_demo')) {
            $this->error('CRITICAL ERROR: demo:reset is only permitted in demo, local, development, or testing environments.');
            Log::alert('Blocked attempt to run demo:reset in non-demo environment: '.app()->environment());

            return self::FAILURE;
        }

        // Layer 3: Database Name Safety Check. An allowlist, not a denylist: the
        // production database is named `recruivo_db`, which no list of
        // production-ish words catches, and the check below runs `migrate:fresh`
        // against whatever the connection names. A database this command is allowed
        // to wipe therefore has to say so in its own name; anything else is refused.
        $databaseName = strtolower((string) config('database.connections.'.config('database.default').'.database'));
        if (preg_match('/(demo|test|local|dev)/', $databaseName) !== 1) {
            $this->error('CRITICAL ERROR: demo:reset only wipes a demo, test, local or development database; refusing "'.$databaseName.'".');
            Log::alert('Blocked demo:reset against a database whose name does not identify it as demo/test/local: '.$databaseName);

            return self::FAILURE;
        }
        if (! $this->option('force') && $this->input->isInteractive()) {
            if (! $this->confirm('This will wipe all data and restore the demo environment to its canonical seeded state. Continue?')) {
                $this->info('Demo reset cancelled.');

                return self::SUCCESS;
            }
        }

        $this->info('Starting demo environment reset...');
        Log::info('Demo environment reset initiated.');

        $this->comment('Clearing runtime caches...');
        $this->call('cache:clear');
        $this->call('view:clear');

        $this->comment('Re-migrating and seeding canonical dataset...');
        $exitCode = $this->call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            $this->error('Failed to run migrate:fresh --seed during demo reset.');

            return self::FAILURE;
        }

        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // Ignore if cache driver doesn't support flush
        }

        $this->info('✓ Demo environment successfully restored to canonical state!');
        Log::info('Demo environment reset completed successfully.');

        return self::SUCCESS;
    }
}
