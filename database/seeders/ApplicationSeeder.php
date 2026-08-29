<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ApplicationStatusUpdatedNotification;
use App\Notifications\NewApplicationNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating realistic applications, interview schedules, saved jobs, and notifications...');

        $publishedJobs = Job::where('status', JobStatus::Published->value)->with('company')->get();

        if ($publishedJobs->isEmpty()) {
            $this->command->warn('No published jobs found. Please run JobSeeder first.');

            return;
        }

        $demoCandidate = User::where('email', 'candidate@recruivo.work')->first();
        $candidates = User::where('is_recruiter', false)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Candidate'))
            ->get();

        if ($candidates->isEmpty()) {
            $this->command->warn('No candidates found. Please run UserSeeder first.');

            return;
        }

        if (Application::count() >= 20) {
            $this->command->info('Applications already seeded. Ensuring saved jobs and notifications...');
            $this->seedSavedJobs($demoCandidate, $candidates->filter(fn ($c) => $c->id !== $demoCandidate?->id), $publishedJobs);
            $this->seedDemoNotifications();

            return;
        }

        // 1. Seed Demo Candidate applications with a rich, realistic pipeline
        if ($demoCandidate) {
            $demoJobs = [
                ['company' => 'aetheris-dynamics', 'status' => ApplicationStatus::Interview, 'notes' => 'Strong background in cloud architecture. Scheduled for technical interview.', 'interview' => true],
                ['company' => 'omnistack-engineering', 'status' => ApplicationStatus::Accepted, 'notes' => 'Outstanding technical assessment. Offer extended and accepted.', 'interview' => false],
                ['company' => 'bitforge-software', 'status' => ApplicationStatus::Shortlisted, 'notes' => 'Profile passed initial screening. Moving forward to recruiter screen.', 'interview' => false],
                ['company' => 'echologic-ai', 'status' => ApplicationStatus::Pending, 'notes' => null, 'interview' => false],
                ['company' => 'cipherwave-security', 'status' => ApplicationStatus::Rejected, 'notes' => 'Selected candidate with specialized cryptographic hardware experience.', 'interview' => false],
            ];

            foreach ($demoJobs as $spec) {
                $job = $publishedJobs->first(fn ($j) => $j->company?->slug === $spec['company'])
                    ?? $publishedJobs->random();

                $createdAt = now()->subDays(rand(8, 30));

                $app = Application::firstOrCreate(
                    ['candidate_id' => $demoCandidate->id, 'job_id' => $job->id],
                    [
                        'resume_path' => 'resumes/demo-candidate-resume.pdf',
                        'cover_letter' => "I am applying for the {$job->title} role at {$job->company?->name}. My experience in designing scalable services and delivering clean full-stack features aligns closely with your engineering goals. Thank you for your consideration.",
                        'status' => $spec['status']->value,
                        'original_status' => ApplicationStatus::Pending->value,
                        'status_changed' => in_array($spec['status'], [ApplicationStatus::Accepted, ApplicationStatus::Rejected]),
                        'status_changed_at' => in_array($spec['status'], [ApplicationStatus::Accepted, ApplicationStatus::Rejected]) ? now()->subDays(2) : null,
                        'notes_added' => filled($spec['notes']),
                        'notes_added_at' => filled($spec['notes']) ? now()->subDays(3) : null,
                        'notes' => $spec['notes'],
                        'interview_at' => $spec['interview'] ? now()->addDays(2)->setHour(14)->setMinute(0) : null,
                        'interview_mode' => $spec['interview'] ? 'remote' : 'onsite',
                        'interview_url' => $spec['interview'] ? 'https://meet.google.com/rec-demo-cloud' : null,
                        'interview_instructions' => $spec['interview'] ? 'Please prepare a 15-minute walkthrough of a distributed system or API architecture you designed.' : null,
                        'created_at' => $createdAt,
                        'updated_at' => now()->subDays(1),
                    ]
                );

                if ($spec['status'] !== ApplicationStatus::Pending) {
                    $app->statusEvents()->create([
                        'changed_by_user_id' => $job->recruiter_id,
                        'from_status' => ApplicationStatus::Pending->value,
                        'to_status' => $spec['status']->value,
                        'created_at' => now()->subDays(3),
                    ]);
                }
            }
        }

        // 2. Seed pool candidate applications
        $poolCandidates = $candidates->filter(fn ($c) => $c->id !== $demoCandidate?->id);
        foreach ($publishedJobs as $jobIndex => $job) {
            // Varied distribution: top companies have active pipelines, some jobs have 0
            $appTarget = match ($job->company?->slug) {
                'aetheris-dynamics' => 2,
                'omnistack-engineering', 'datavortex-systems', 'quantumleap-it' => ($jobIndex % 2 === 0) ? 2 : 1,
                'bitforge-software', 'echologic-ai', 'fluxcore-technologies', 'hyperion-networks' => ($jobIndex % 2 === 0) ? 1 : 0,
                default => ($jobIndex % 3 === 0) ? 1 : 0,
            };

            if ($appTarget === 0) {
                continue;
            }

            $appliedCandidates = $poolCandidates->random(min($appTarget, $poolCandidates->count()));

            foreach ($appliedCandidates as $candidateIndex => $candidate) {
                if (Application::where('candidate_id', $candidate->id)->where('job_id', $job->id)->exists()) {
                    continue;
                }

                $createdAt = now()->subDays(rand(2, 60));

                // Varied statuses with realistic probabilities
                $statusPool = [
                    ApplicationStatus::Pending,
                    ApplicationStatus::Pending,
                    ApplicationStatus::Pending,
                    ApplicationStatus::Shortlisted,
                    ApplicationStatus::Shortlisted,
                    ApplicationStatus::Interview,
                    ApplicationStatus::Accepted,
                    ApplicationStatus::Rejected,
                    ApplicationStatus::Withdrawn,
                ];
                $status = $statusPool[$candidateIndex % count($statusPool)];

                $notes = match ($status) {
                    ApplicationStatus::Shortlisted => 'Resume matches key requirements. Qualified for first-round interview.',
                    ApplicationStatus::Interview => 'Interview scheduled with hiring team. Reviewing system design background.',
                    ApplicationStatus::Accepted => 'Candidate accepted offer letter. Onboarding process initiated.',
                    ApplicationStatus::Rejected => 'Application reviewed. Another candidate possessed closer alignment with specific required tools.',
                    ApplicationStatus::Withdrawn => 'Candidate accepted an offer elsewhere and withdrew application.',
                    default => null,
                };

                $isInterview = ($status === ApplicationStatus::Interview);
                $interviewModes = ['remote', 'onsite', 'phone'];
                $interviewMode = $isInterview ? $interviewModes[$candidateIndex % 3] : 'onsite';
                $interviewAt = $isInterview ? now()->addDays(rand(1, 7))->setHour(rand(10, 16))->setMinute(0) : null;
                $interviewUrl = ($interviewMode === 'remote') ? 'https://meet.google.com/rec-'.substr(md5($job->id.$candidate->id), 0, 8) : null;
                $interviewLocation = ($interviewMode === 'onsite') ? "{$job->company?->name} HQ, {$job->location}" : null;
                $interviewInstructions = $isInterview ? 'Please have your resume and portfolio available for discussion.' : null;

                $app = Application::create([
                    'candidate_id' => $candidate->id,
                    'job_id' => $job->id,
                    'resume_path' => 'resumes/demo-candidate-resume.pdf',
                    'cover_letter' => "I am pleased to apply for the {$job->title} position at {$job->company?->name}. My technical skills and collaborative background make me confident in contributing effectively to your engineering goals.",
                    'status' => $status->value,
                    'original_status' => ApplicationStatus::Pending->value,
                    'status_changed' => in_array($status, [ApplicationStatus::Accepted, ApplicationStatus::Rejected]),
                    'status_changed_at' => in_array($status, [ApplicationStatus::Accepted, ApplicationStatus::Rejected]) ? $createdAt->copy()->addDays(5) : null,
                    'notes_added' => filled($notes),
                    'notes_added_at' => filled($notes) ? $createdAt->copy()->addDays(2) : null,
                    'notes' => $notes,
                    'interview_at' => $interviewAt,
                    'interview_mode' => $interviewMode,
                    'interview_url' => $interviewUrl,
                    'interview_location' => $interviewLocation,
                    'interview_instructions' => $interviewInstructions,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addDays(rand(1, 4)),
                ]);

                if ($status !== ApplicationStatus::Pending) {
                    $app->statusEvents()->create([
                        'changed_by_user_id' => $job->recruiter_id,
                        'from_status' => ApplicationStatus::Pending->value,
                        'to_status' => $status->value,
                        'created_at' => $createdAt->copy()->addDays(2),
                    ]);
                }
            }
        }

        // 3. Seed Saved Jobs
        $this->seedSavedJobs($demoCandidate, $poolCandidates, $publishedJobs);

        // 4. Seed Notifications for Demo Recruiter and Demo Candidate
        $this->seedDemoNotifications();

        $totalApplications = Application::count();
        $this->command->info("{$totalApplications} applications created with status events, interview plans, and notes!");

        // Show status breakdown
        foreach (ApplicationStatus::cases() as $status) {
            $count = Application::where('status', $status->value)->count();
            $this->command->info("  {$status->value}: {$count}");
        }
    }

    private function seedSavedJobs(?User $demoCandidate, $poolCandidates, $publishedJobs): void
    {
        // Demo candidate saves 4 interesting jobs
        if ($demoCandidate) {
            $jobsToSave = $publishedJobs->random(min(4, $publishedJobs->count()));
            $demoCandidate->savedJobs()->syncWithoutDetaching($jobsToSave->pluck('id'));
        }

        // Other candidates save 1-3 jobs each
        foreach ($poolCandidates->take(30) as $candidate) {
            $saveCount = rand(1, 3);
            $jobsToSave = $publishedJobs->random(min($saveCount, $publishedJobs->count()));
            $candidate->savedJobs()->syncWithoutDetaching($jobsToSave->pluck('id'));
        }

        $this->command->info('Saved jobs populated for candidates.');
    }

    private function seedDemoNotifications(): void
    {
        $demoRecruiter = User::where('email', 'recruiter@recruivo.work')->first();
        $demoCandidate = User::where('email', 'candidate@recruivo.work')->first();

        if ($demoRecruiter) {
            // Find applications to recruiter's jobs
            $recruiterApps = Application::whereHas('job', fn ($q) => $q->where('company_id', $demoRecruiter->company_id))
                ->with(['job', 'candidate'])
                ->take(4)
                ->get();

            foreach ($recruiterApps as $index => $app) {
                $notification = new NewApplicationNotification($app);
                $demoRecruiter->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => get_class($notification),
                    'data' => $notification->toArray($demoRecruiter),
                    'read_at' => ($index >= 2) ? now()->subDays($index) : null,
                    'created_at' => now()->subHours(($index + 1) * 6),
                    'updated_at' => now()->subHours(($index + 1) * 6),
                ]);
            }
        }

        if ($demoCandidate) {
            $candidateApps = Application::where('candidate_id', $demoCandidate->id)
                ->where('status', '!=', ApplicationStatus::Pending->value)
                ->with(['job.company'])
                ->take(3)
                ->get();

            foreach ($candidateApps as $index => $app) {
                $notification = new ApplicationStatusUpdatedNotification($app);
                $demoCandidate->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => get_class($notification),
                    'data' => $notification->toArray($demoCandidate),
                    'read_at' => ($index >= 1) ? now()->subDays(1) : null,
                    'created_at' => now()->subHours(($index + 1) * 12),
                    'updated_at' => now()->subHours(($index + 1) * 12),
                ]);
            }
        }

        $this->command->info('Notifications populated for demo accounts.');
    }
}
