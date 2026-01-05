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
            'headline' => $this->faker->sentence(),
            'experience' => $this->faker->paragraphs(3, true),
            'skills' => implode(', ', $this->faker->words(6)),
            'resume_path' => 'resumes/'.$this->faker->uuid.'.pdf',
            'linkedin_url' => $this->faker->url(),
            'portfolio_url' => $this->faker->url(),
        ];
    }
}
