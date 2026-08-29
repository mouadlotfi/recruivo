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
        $this->command->info('Syncing IT jobs with realistic compensation, remote types, and schedules...');

        $companies = Company::query()->orderBy('id')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please run CompanySeeder first.');

            return;
        }

        $jobCountsByCompany = [
            'aetheris-dynamics' => 7,
            'bitforge-software' => 5,
            'cipherwave-security' => 4,
            'datavortex-systems' => 6,
            'echologic-ai' => 5,
            'fluxcore-technologies' => 4,
            'gigabyte-foundry' => 5,
            'hyperion-networks' => 5,
            'ionsphere-labs' => 3,
            'krypton-solutions' => 4,
            'lumina-software-house' => 4,
            'nexusnode-tech' => 3,
            'omnistack-engineering' => 6,
            'pixelcraft-digital' => 3,
            'quantumleap-it' => 5,
        ];

        $remoteTypes = ['remote', 'hybrid', 'onsite'];

        foreach ($companies as $company) {
            $profileKey = array_key_exists($company->slug, self::JOB_PROFILES)
                ? $company->slug
                : Str::slug($company->name);
            $profile = self::JOB_PROFILES[$profileKey] ?? self::DEFAULT_PROFILE;
            $recruiters = $company->recruiters()->get();

            if ($recruiters->isEmpty()) {
                continue;
            }

            $targetCount = $jobCountsByCompany[$company->slug] ?? 4;
            $existingJobs = $company->jobs()->orderBy('id')->get();

            for ($i = 0; $i < $targetCount; $i++) {
                $job = $existingJobs->get($i) ?? new Job;

                $title = $this->vacancyTitle($profile['titles'], $i);
                $location = $profile['locations'][$i % count($profile['locations'])];
                $remoteType = $remoteTypes[$i % count($remoteTypes)];

                // Realistic compensation based on seniority
                $salaryRange = $this->salaryRangeForTitle($title);

                // Status: majority published, 1 draft per company at most
                $isDraft = ($i === $targetCount - 1 && $targetCount >= 5);
                $status = $isDraft ? JobStatus::Draft->value : JobStatus::Published->value;

                // Published date staggered over past 3 months
                $publishedAt = $isDraft ? null : now()->subDays(($i * 12) + rand(1, 8));

                // Closes_at: closing soon for one job, expired for another, null for rest
                $closesAt = null;
                if (! $isDraft) {
                    if ($i === 1) {
                        // Closing soon (in 3 to 6 days)
                        $closesAt = now()->addDays(rand(3, 6));
                    } elseif ($i === $targetCount - 2 && $targetCount >= 5) {
                        // Expired (closed 5 days ago)
                        $closesAt = now()->subDays(rand(4, 12));
                    }
                }

                $job->fill([
                    'company_id' => $company->id,
                    'recruiter_id' => $recruiters->random()->id,
                    'title' => $title,
                    'category' => $profile['category'],
                    'location' => $location,
                    'remote_type' => $remoteType,
                    'salary_min' => $salaryRange['min'],
                    'salary_max' => $salaryRange['max'],
                    'status' => $status,
                    'published_at' => $publishedAt,
                    'closes_at' => $closesAt,
                ]);
                $job->setRelation('company', $company);
                $job->description = $content->jobDescription($job);
                $job->save();
            }
        }

        $totalJobs = Job::count();
        $publishedCount = Job::published()->count();
        $this->command->info("All seeded jobs synced: {$totalJobs} total ({$publishedCount} active published).");
    }

    private function salaryRangeForTitle(string $title): array
    {
        if (str_starts_with($title, 'Principal')) {
            return ['min' => 165000, 'max' => 220000];
        }
        if (str_starts_with($title, 'Lead')) {
            return ['min' => 145000, 'max' => 185000];
        }
        if (str_starts_with($title, 'Senior')) {
            return ['min' => 115000, 'max' => 155000];
        }

        return ['min' => 75000, 'max' => 105000];
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
