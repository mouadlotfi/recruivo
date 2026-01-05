<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\CandidateProfile;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating users...');

        // Create Admin User
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@recruivo.work',
            'password' => bcrypt('password'),
            'is_recruiter' => false,
        ]);
        $admin->assignRole('Admin');
        $this->command->info('Admin user created: admin@recruivo.work / password');

        // Create a demo recruiter for the first company
        $companies = Company::all();
        $firstCompany = $companies->first();
        
        $demoRecruiter = User::factory()->create([
            'name' => 'Demo Recruiter',
            'email' => 'recruiter@recruivo.work',
            'password' => bcrypt('password'),
            'is_recruiter' => true,
            'company_id' => $firstCompany->id,
            'job_title' => 'HR Manager',
        ]);
        $demoRecruiter->assignRole('Recruiter');
        $this->command->info('Demo recruiter created: recruiter@recruivo.work / password');

        // Create Recruiter Users (one for each remaining company)
        foreach ($companies->skip(1) as $company) {
            $recruiter = User::factory()->create([
                'name' => 'Recruiter at ' . $company->name,
                'email' => 'recruiter@' . str_replace(' ', '', strtolower($company->name)) . '.com',
                'password' => bcrypt('password'),
                'is_recruiter' => true,
                'company_id' => $company->id,
                'job_title' => fake()->randomElement(['HR Manager', 'Talent Acquisition', 'Recruitment Lead']),
            ]);
            $recruiter->assignRole('Recruiter');
        }
        $this->command->info($companies->count() . ' recruiter users created!');

        // Create additional recruiters for some companies (2-3 per company for larger ones)
        $largeCompanies = $companies->random(min(5, $companies->count()));
        foreach ($largeCompanies as $company) {
            $additionalRecruiters = rand(1, 2);
            for ($i = 0; $i < $additionalRecruiters; $i++) {
                $recruiter = User::factory()->create([
                    'is_recruiter' => true,
                    'company_id' => $company->id,
                    'job_title' => fake()->randomElement(['HR Specialist', 'Talent Scout', 'Recruiter']),
                ]);
                $recruiter->assignRole('Recruiter');
            }
        }
        $this->command->info('Additional recruiters created for larger companies!');

        // Create Candidate Users with profiles
        $this->command->info('Creating candidate users...');
        
        // Create a demo candidate
        $demoCandidate = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'candidate@recruivo.work',
            'password' => bcrypt('password'),
            'is_recruiter' => false,
            'location' => 'San Francisco, CA',
        ]);
        $demoCandidate->assignRole('Candidate');
        
        CandidateProfile::factory()->create([
            'user_id' => $demoCandidate->id,
            'headline' => 'Senior Software Engineer',
            'skills' => 'PHP, Laravel, Vue.js, MySQL, Redis, AWS',
        ]);
        
        $this->command->info('Demo candidate created: candidate@recruivo.work / password');

        // Create 50 random candidates with profiles
        User::factory()
            ->count(50)
            ->create([
                'is_recruiter' => false,
            ])
            ->each(function ($user) {
                $user->assignRole('Candidate');
                
                // Create candidate profile for each candidate
                CandidateProfile::factory()->create([
                    'user_id' => $user->id,
                ]);
            });

        $this->command->info('50 candidate users with profiles created!');
        
        $totalUsers = User::count();
        $this->command->info("Total users created: {$totalUsers}");
    }
}

