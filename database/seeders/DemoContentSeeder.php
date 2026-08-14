<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\Post;
use App\Models\User;
use App\Services\DemoContentService;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(DemoContentService $content): void
    {
        Company::query()->orderBy('id')->each(function (Company $company) use ($content) {
            $company->update($content->companyDetails($company));
        });

        Job::query()->with('company')->orderBy('id')->each(function (Job $job) use ($content) {
            $job->update(['description' => $content->jobDescription($job)]);
        });

        User::query()->with(['company', 'candidateProfile'])->orderBy('id')->each(function (User $user) use ($content) {
            $details = $content->userDetails($user);
            $user->update([
                'location' => $details['location'],
                'profile_summary' => $details['profile_summary'],
            ]);

            if (!$user->isRecruiter() && isset($details['profile'])) {
                $user->candidateProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'headline' => $details['profile']['headline'],
                        'skills' => $details['profile']['skills'],
                        'experience' => $details['profile']['experience'],
                        'education' => $details['profile']['education'],
                        'languages' => 'English, French',
                    ]
                );
            }
        });

        Application::query()->with(['job.company'])->orderBy('id')->each(function (Application $application) use ($content) {
            $application->update($content->applicationDetails($application));
        });

        Post::query()->orderBy('id')->each(function (Post $post, int $index) use ($content) {
            $article = $content->article($index);
            $post->setTranslations('title', $article['title']);
            $post->setTranslations('slug', collect($article['title'])->map(fn (string $title) => str($title)->slug()->toString())->all());
            $post->setTranslations('content', $article['content']);
            $post->save();
        });

        $this->command?->info('Curated demo content and locations synchronized.');
    }
}
