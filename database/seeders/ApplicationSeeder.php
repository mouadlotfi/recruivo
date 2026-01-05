<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating applications...');

        // Get all published jobs
        $jobs = Job::where('status', JobStatus::Published->value)->get();
        
        if ($jobs->isEmpty()) {
            $this->command->warn('No published jobs found. Please run JobSeeder first.');
            return;
        }

        // Get all candidate users
        $candidates = User::where('is_recruiter', false)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Candidate');
            })
            ->get();

        if ($candidates->isEmpty()) {
            $this->command->warn('No candidates found. Please run UserSeeder first.');
            return;
        }

        // Create applications: each candidate applies to 2-5 random jobs
        foreach ($candidates as $candidate) {
            $applicationCount = rand(2, 5);
            $jobsToApply = $jobs->random(min($applicationCount, $jobs->count()));

            foreach ($jobsToApply as $job) {
                // Check if application already exists
                $exists = Application::where('candidate_id', $candidate->id)
                    ->where('job_id', $job->id)
                    ->exists();

                if (!$exists) {
                    Application::factory()->create([
                        'candidate_id' => $candidate->id,
                        'job_id' => $job->id,
                        'status' => fake()->randomElement([
                            ApplicationStatus::Pending->value,
                            ApplicationStatus::Pending->value,
                            ApplicationStatus::Pending->value,
                            ApplicationStatus::Pending->value,
                            ApplicationStatus::Pending->value,
                            ApplicationStatus::Accepted->value,
                            ApplicationStatus::Rejected->value,
                            ApplicationStatus::Rejected->value,
                        ]),
                    ]);
                }
            }
        }

        $totalApplications = Application::count();
        $this->command->info("{$totalApplications} applications created successfully!");

        // Show status breakdown
        foreach (ApplicationStatus::cases() as $status) {
            $count = Application::where('status', $status->value)->count();
            $this->command->info("  {$status->value}: {$count}");
        }
    }
}

