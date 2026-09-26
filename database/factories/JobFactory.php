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
            'description' => 'Join a collaborative engineering team to deliver reliable product improvements, solve practical customer problems, and improve the quality of the systems you own.',
            'location' => $this->faker->randomElement(['Casablanca, Morocco', 'Dublin, Ireland', 'Berlin, Germany', 'Paris, France', 'London, United Kingdom']),
            'category' => $this->faker->randomElement(['Engineering', 'Design', 'Marketing']),
            'remote_type' => $this->faker->randomElement(['remote', 'hybrid', 'onsite']),
            'salary_min' => $this->faker->numberBetween(50000, 80000),
            'salary_max' => $this->faker->numberBetween(80000, 150000),
            'status' => $this->faker->randomElement([JobStatus::Draft->value, JobStatus::Published->value]),
            'published_at' => now(),
            'closes_at' => null,
        ];
    }
}
