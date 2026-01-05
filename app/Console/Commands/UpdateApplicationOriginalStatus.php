<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;

class UpdateApplicationOriginalStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-application-original-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing applications to set original_status field';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $applications = Application::whereNull('original_status')->get();
        
        $this->info("Found {$applications->count()} applications without original_status");
        
        foreach ($applications as $application) {
            $application->update(['original_status' => $application->status]);
            $this->line("Updated application {$application->id} with original_status: {$application->original_status}");
        }
        
        $this->info('All applications updated successfully!');
    }
}
