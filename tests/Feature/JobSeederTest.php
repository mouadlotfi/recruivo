<?php

namespace Tests\Feature;

use Database\Seeders\JobSeeder;
use ReflectionClass;
use Tests\TestCase;

class JobSeederTest extends TestCase
{
    public function test_job_seeder_generates_distinct_titles_after_the_base_role_cycle(): void
    {
        $seeder = (new ReflectionClass(JobSeeder::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(JobSeeder::class))->getMethod('vacancyTitle');
        $titles = ['IT Consultant', 'Solutions Architect', 'Technology Strategy Consultant'];

        $generated = collect(range(0, 6))
            ->map(fn (int $index) => $method->invoke($seeder, $titles, $index));

        $this->assertCount(7, $generated->unique());
        $this->assertSame([
            'IT Consultant',
            'Solutions Architect',
            'Technology Strategy Consultant',
            'Senior IT Consultant',
            'Senior Solutions Architect',
            'Senior Technology Strategy Consultant',
            'Lead IT Consultant',
        ], $generated->all());
    }

    public function test_seed_profiles_do_not_contain_the_replaced_location(): void
    {
        $source = file_get_contents(database_path('seeders/JobSeeder.php'));

        $this->assertStringContainsString('Ramallah, Palestine', $source);
        $this->assertStringNotContainsString('Tel Aviv, Israel', $source);
    }
}
