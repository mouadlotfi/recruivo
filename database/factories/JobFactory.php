<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    public function definition(): array
    {
        $companyFactory = Company::factory();

        return [
            'recruiter_id' => User::factory()->for($companyFactory),
            'company_id' => $companyFactory,
            'title' => $this->faker->jobTitle(),
            'description' => $this->faker->paragraphs(3, true),
            'location' => $this->faker->city(),
            'category' => $this->faker->randomElement(['Engineering', 'Design', 'Marketing']),
            'remote_type' => $this->faker->randomElement(['remote', 'hybrid', 'on-site']),
            'salary_min' => $this->faker->numberBetween(50000, 80000),
            'salary_max' => $this->faker->numberBetween(80000, 150000),
            'status' => $this->faker->randomElement([JobStatus::Draft->value, JobStatus::Published->value]),
            'published_at' => now(),
        ];
    }
}
