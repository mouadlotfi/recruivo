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
            'cover_letter' => $this->faker->paragraphs(2, true),
            'status' => $this->faker->randomElement(array_map(fn ($status) => $status->value, ApplicationStatus::cases())),
        ];
    }
}
