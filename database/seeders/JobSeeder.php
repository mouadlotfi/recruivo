<?php

namespace Database\Seeders;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating jobs...');

        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please run CompanySeeder first.');
            return;
        }

        $jobTitles = [
            'Senior Software Engineer',
            'Frontend Developer',
            'Backend Developer',
            'Full Stack Developer',
            'DevOps Engineer',
            'Product Manager',
            'UI/UX Designer',
            'Data Scientist',
            'Marketing Manager',
            'Sales Representative',
            'Customer Success Manager',
            'Business Analyst',
            'QA Engineer',
            'Security Engineer',
            'Mobile Developer',
        ];

        $categories = [
            'Engineering',
            'Design',
            'Marketing',
            'Sales',
            'Product',
            'Data Science',
            'Customer Success',
            'Operations',
        ];

        foreach ($companies as $company) {
            // Get recruiters for this company
            $recruiters = User::where('company_id', $company->id)
                ->where('is_recruiter', true)
                ->get();

            if ($recruiters->isEmpty()) {
                continue;
            }

            // Create 3-7 jobs per company
            $jobCount = rand(3, 7);
            
            for ($i = 0; $i < $jobCount; $i++) {
                Job::factory()->create([
                    'company_id' => $company->id,
                    'recruiter_id' => $recruiters->random()->id,
                    'title' => fake()->randomElement($jobTitles),
                    'category' => fake()->randomElement($categories),
                    'status' => fake()->randomElement([
                        JobStatus::Published->value,
                        JobStatus::Published->value,
                        JobStatus::Published->value,
                        JobStatus::Draft->value, // Less drafts
                    ]),
                    'published_at' => fake()->dateTimeBetween('-3 months', 'now'),
                ]);
            }
        }

        $totalJobs = Job::count();
        $publishedJobs = Job::where('status', JobStatus::Published->value)->count();
        $draftJobs = Job::where('status', JobStatus::Draft->value)->count();

        $this->command->info("Jobs created successfully!");
        $this->command->info("Total: {$totalJobs} (Published: {$publishedJobs}, Draft: {$draftJobs})");
    }
}

