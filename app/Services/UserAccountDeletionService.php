<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class UserAccountDeletionService
{
    public function deleteUserAccount(User $user, bool $initiatedByUser = true): void
    {
        $user->loadMissing(['candidateProfile', 'jobs.applications']);

        $email = $user->email;
        $name = $user->name ?? $user->email; // For recruiters who might not have a name

        $paths = new Collection();

        if ($user->profile_picture_path) {
            $paths->push($user->profile_picture_path);
        }

        if ($user->candidateProfile?->resume_path) {
            $paths->push($user->candidateProfile->resume_path);
        }

        $candidateApplicationResumes = $user->applications()
            ->pluck('resume_path')
            ->filter()
            ->all();

        $paths = $paths->merge($candidateApplicationResumes);

        if ($user->hasRole('Recruiter')) {
            $recruiterApplicationResumes = $user->jobs
                ->flatMap(fn ($job) => $job->applications->pluck('resume_path'))
                ->filter()
                ->all();

            $paths = $paths->merge($recruiterApplicationResumes);
        }

        $paths->filter()
            ->unique()
            ->each(function (string $path) {
                Storage::disk('public')->delete($path);
            });

        $user->tokens()->delete();

        $user->delete();

        // Send email notification that account was deleted
        Notification::route('mail', $email)
            ->notify(new AccountDeletedNotification($name, $initiatedByUser));
    }
}
