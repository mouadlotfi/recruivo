<?php

namespace Database\Factories;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateProfileFactory extends Factory
{
    protected $model = CandidateProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'headline' => 'Software Engineer',
            'experience' => 'Built and maintained customer-facing software, improved automated test coverage, and collaborated with product teams to deliver reliable features.',
            'education' => 'Bachelor’s degree in Computer Science',
            'languages' => 'English, French',
            'skills' => 'PHP, Laravel, JavaScript, SQL, Docker, Automated Testing',
            'resume_path' => 'resumes/'.$this->faker->uuid.'.pdf',
            'linkedin_url' => $this->faker->url(),
            'portfolio_url' => $this->faker->url(),
            'github_url' => 'https://github.com/'.$this->faker->userName(),
            'website_url' => $this->faker->url(),
        ];
    }
}
