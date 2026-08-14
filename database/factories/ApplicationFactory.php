<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'candidate_id' => User::factory(),
            'job_id' => Job::factory(),
            'resume_path' => 'resumes/'.$this->faker->uuid.'.pdf',
            'cover_letter' => 'I am interested in this position because my experience aligns with the role’s focus. I would welcome the opportunity to discuss how I can contribute to the team.',
            'status' => ApplicationStatus::Pending->value,
            'original_status' => ApplicationStatus::Pending->value,
            'status_changed' => false,
            'notes_added' => false,
            'notes' => null,
            'interview_at' => null,
            'interview_mode' => 'onsite',
            'interview_location' => null,
            'interview_url' => null,
            'interview_instructions' => null,
        ];
    }
}
