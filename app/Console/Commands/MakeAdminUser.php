<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MakeAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin
                            {--name= : The full name of the administrator}
                            {--email= : The email address of the administrator}
                            {--password= : The password for the administrator account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new administrator account with verified email and Admin role';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Administrator name');
        $email = $this->option('email') ?? $this->ask('Administrator email address');
        $password = $this->option('password') ?? $this->secret('Administrator password (minimum 8 characters)');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // Ensure Admin role exists in Spatie permission tables
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_demo' => false,
            'is_recruiter' => false,
        ]);

        $user->assignRole('Admin');

        $this->info("✓ Administrator account created successfully: {$user->email} ({$user->name})");

        return self::SUCCESS;
    }
}
