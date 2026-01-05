<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating companies...');

        // Create 15 companies
        Company::factory()
            ->count(15)
            ->create();

        $this->command->info('15 companies created successfully!');
    }
}

