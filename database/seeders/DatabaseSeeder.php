<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
            JobSeeder::class,
            ApplicationSeeder::class,
            PostSeeder::class,
        ]);
    }
}
