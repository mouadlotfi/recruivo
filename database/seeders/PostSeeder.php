<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating blog posts...');

        // Get admin and recruiter users who can create posts
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->get();

        $recruiters = User::where('is_recruiter', true)->take(5)->get();
        
        $authors = $admins->concat($recruiters);

        if ($authors->isEmpty()) {
            $this->command->warn('No admin or recruiter users found. Please run UserSeeder first.');
            return;
        }

        // Create 20 published posts
        Post::factory()
            ->count(20)
            ->published()
            ->create([
                'user_id' => fn() => $authors->random()->id,
            ]);

        // Create 5 draft posts
        Post::factory()
            ->count(5)
            ->draft()
            ->create([
                'user_id' => fn() => $authors->random()->id,
            ]);

        $totalPosts = Post::count();
        $publishedPosts = Post::where('is_published', true)->count();
        $draftPosts = Post::where('is_published', false)->count();

        $this->command->info("Posts created successfully!");
        $this->command->info("Total: {$totalPosts} (Published: {$publishedPosts}, Draft: {$draftPosts})");
    }
}

