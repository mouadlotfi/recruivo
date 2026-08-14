<?php

namespace Database\Seeders;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Services\DemoContentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobSeeder extends Seeder
{
    private const JOB_PROFILES = [
        'aetheris-dynamics' => ['category' => 'Cloud Computing', 'titles' => ['Cloud Infrastructure Engineer', 'Site Reliability Engineer', 'Cloud Solutions Architect'], 'locations' => ['Seattle, United States', 'Singapore, Singapore', 'Dublin, Ireland']],
        'bitforge-software' => ['category' => 'Software Development', 'titles' => ['Software Engineer', 'Backend Developer', 'Application Architect'], 'locations' => ['Toronto, Canada', 'Berlin, Germany', 'Bengaluru, India']],
        'cipherwave-security' => ['category' => 'Cybersecurity', 'titles' => ['Cybersecurity Engineer', 'Encryption Engineer', 'Security Operations Analyst'], 'locations' => ['Ramallah, Palestine', 'Tallinn, Estonia', 'Washington, United States']],
        'datavortex-systems' => ['category' => 'Data Analytics', 'titles' => ['Data Engineer', 'Big Data Analyst', 'Analytics Platform Engineer'], 'locations' => ['London, United Kingdom', 'Tokyo, Japan', 'Sao Paulo, Brazil']],
        'echologic-ai' => ['category' => 'Artificial Intelligence', 'titles' => ['Machine Learning Engineer', 'AI Research Engineer', 'Computer Vision Engineer'], 'locations' => ['San Francisco, United States', 'Seoul, South Korea', 'Montreal, Canada']],
        'fluxcore-technologies' => ['category' => 'DevOps', 'titles' => ['DevOps Engineer', 'Platform Engineer', 'Cloud Operations Engineer'], 'locations' => ['Amsterdam, Netherlands', 'Austin, United States', 'Warsaw, Poland']],
        'gigabyte-foundry' => ['category' => 'Hardware Systems', 'titles' => ['Systems Engineer', 'Hardware Integration Engineer', 'Infrastructure Hardware Engineer'], 'locations' => ['Taipei, Taiwan', 'Shenzhen, China', 'Munich, Germany']],
        'hyperion-networks' => ['category' => 'Networking', 'titles' => ['Network Engineer', 'Telecommunications Engineer', 'Network Architect'], 'locations' => ['Stockholm, Sweden', 'Dubai, United Arab Emirates', 'Sydney, Australia']],
        'ionsphere-labs' => ['category' => 'Quantum Computing', 'titles' => ['Quantum Software Engineer', 'Quantum Researcher', 'Quantum Systems Engineer'], 'locations' => ['Geneva, Switzerland', 'Oxford, United Kingdom', 'Boston, United States']],
        'krypton-solutions' => ['category' => 'Information Security', 'titles' => ['Information Security Analyst', 'Security Architect', 'Security Compliance Engineer'], 'locations' => ['Zurich, Switzerland', 'Singapore, Singapore', 'Canberra, Australia']],
        'lumina-software-house' => ['category' => 'Web Development', 'titles' => ['Frontend Developer', 'UX Engineer', 'Web Application Developer'], 'locations' => ['Paris, France', 'Barcelona, Spain', 'Lisbon, Portugal']],
        'nexusnode-tech' => ['category' => 'IoT Systems', 'titles' => ['IoT Solutions Engineer', 'Embedded Systems Engineer', 'Smart Systems Developer'], 'locations' => ['Helsinki, Finland', 'Eindhoven, Netherlands', 'Tokyo, Japan']],
        'omnistack-engineering' => ['category' => 'Full-Stack Development', 'titles' => ['Full-Stack Developer', 'Platform Engineer', 'Software Architect'], 'locations' => ['New York, United States', 'London, United Kingdom', 'Melbourne, Australia']],
        'pixelcraft-digital' => ['category' => 'Interactive Software', 'titles' => ['Interactive Media Developer', 'Creative Technologist', 'Game Software Engineer'], 'locations' => ['Los Angeles, United States', 'Montreal, Canada', 'Tokyo, Japan']],
        'quantumleap-it' => ['category' => 'IT Consulting', 'titles' => ['IT Consultant', 'Solutions Architect', 'Technology Strategy Consultant'], 'locations' => ['Casablanca, Morocco', 'Dubai, United Arab Emirates', 'New York, United States']],
    ];

    private const DEFAULT_PROFILE = [
        'category' => 'Information Technology',
        'titles' => ['Software Engineer', 'Systems Engineer', 'IT Support Engineer'],
        'locations' => ['Toronto, Canada', 'London, United Kingdom', 'Sydney, Australia'],
    ];

    public function run(DemoContentService $content): void
    {
        $this->command->info('Syncing IT jobs...');

        $companies = Company::query()->orderBy('id')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please run CompanySeeder first.');
            return;
        }

        foreach ($companies as $company) {
            $profileKey = array_key_exists($company->slug, self::JOB_PROFILES)
                ? $company->slug
                : Str::slug($company->name);
            $profile = self::JOB_PROFILES[$profileKey] ?? self::DEFAULT_PROFILE;
            $jobs = $company->jobs()->orderBy('id')->get();

            foreach ($jobs as $index => $job) {
                $job->update([
                    'title' => $this->vacancyTitle($profile['titles'], $index),
                    'category' => $profile['category'],
                    'location' => $profile['locations'][$index % count($profile['locations'])],
                ]);
                $job->load('company');
                $job->update(['description' => $content->jobDescription($job)]);
            }

            if ($jobs->isNotEmpty()) {
                continue;
            }

            $recruiters = $company->recruiters()->get();

            if ($recruiters->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < rand(3, 7); $i++) {
                $job = Job::factory()->create([
                    'company_id' => $company->id,
                    'recruiter_id' => $recruiters->random()->id,
                    'title' => $this->vacancyTitle($profile['titles'], $i),
                    'category' => $profile['category'],
                    'location' => $profile['locations'][$i % count($profile['locations'])],
                    'status' => fake()->randomElement([
                        JobStatus::Published->value,
                        JobStatus::Published->value,
                        JobStatus::Published->value,
                        JobStatus::Draft->value,
                    ]),
                    'published_at' => fake()->dateTimeBetween('-3 months', 'now'),
                ]);
                $job->load('company');
                $job->update(['description' => $content->jobDescription($job)]);
            }
        }

        $this->command->info('All seeded jobs now use IT categories and roles.');
    }

    private function vacancyTitle(array $titles, int $index): string
    {
        $baseTitle = $titles[$index % count($titles)];
        $cycle = intdiv($index, count($titles));

        return match ($cycle) {
            0 => $baseTitle,
            1 => 'Senior '.$baseTitle,
            2 => 'Lead '.$baseTitle,
            3 => 'Principal '.$baseTitle,
            default => 'Principal '.$baseTitle.' '.($cycle - 2),
        };
    }
}
