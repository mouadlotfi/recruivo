<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Company::generateUniqueSlug($name.'-'.Str::random(4)),
            'tagline' => $this->faker->catchPhrase(),
            'location' => $this->faker->city().', '.$this->faker->country(),
            'website_url' => $this->faker->url(),
            'linkedin_url' => 'https://www.linkedin.com/company/'.Str::slug($name),
            'size' => $this->faker->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
            'founded_year' => $this->faker->numberBetween(1990, now()->year),
            'mission' => $this->faker->paragraph(),
            'culture' => $this->faker->paragraph(),
        ];
    }
}
