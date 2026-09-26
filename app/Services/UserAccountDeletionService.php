<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserAccountDeletionService
{
    public function deleteUserAccount(User $user, bool $initiatedByUser = true): void
    {
        if ($user->is_demo) {
            throw ValidationException::withMessages([
                'account' => __('common.demo_account_cannot_be_deleted'),
            ]);
        }

        // An admin-initiated deletion must not touch an Admin target: the web admin
        // panel refuses it, and enforcing the rule here means every caller inherits
        // it instead of only the surface that remembered to check. Self-service
        // deletion passes $initiatedByUser = true and stays available.
        if (! $initiatedByUser && $user->hasRole('Admin')) {
            throw ValidationException::withMessages([
                'account' => __('admin.cannot_delete_admin'),
            ]);
        }

        $user->loadMissing('candidateProfile');

        $email = $user->email;
        $name = $user->name ?? $user->email; // For recruiters who might not have a name

        $paths = new Collection;

        if ($user->profile_picture_path) {
            $paths->push($user->profile_picture_path);
        }

        if ($user->candidateProfile?->resume_path) {
            $paths->push($user->candidateProfile->resume_path);
        }

        $paths = $paths->merge(
            $user->applications()
                ->pluck('resume_path')
                ->filter()
                ->all()
        );

        $company = $user->company()->first();
        $logoPath = $company?->logo_path;
        $orphanedCompany = false;

        DB::transaction(function () use ($user, $company, &$orphanedCompany): void {
            $user->tokens()->delete();

            $user->delete();

            // A company deliberately outlives its last recruiter - the migration
            // docblocks and RecruiterDeletionTest pin that - but nothing may keep
            // publishing vacancies or collecting applications on its behalf once no
            // principal is left to manage them. Nothing in the app can bind a user to
            // an existing company, so the reassignment that design assumes is not
            // reachable; unpublishing is what makes the leftover inert.
            if (! $company || $company->recruiters()->exists()) {
                return;
            }

            $orphanedCompany = true;

            $company->jobs()
                ->where('status', JobStatus::Published->value)
                ->update([
                    'status' => JobStatus::Draft->value,
                    'published_at' => null,
                ]);

            $company->update(['logo_path' => null]);
        });

        // Irreversible file removal and the notification run only after the commit:
        // a transaction that rolls back must not have destroyed anything. The logo
        // goes only when the company was actually orphaned - a company that still has
        // a recruiter keeps both its row and its file.
        $paths->filter()
            ->unique()
            ->each(function (string $path) {
                Storage::disk('private')->delete($path);
            });

        if ($orphanedCompany && $logoPath) {
            Storage::disk('public')->delete($logoPath);
        }

        Notification::route('mail', $email)
            ->notify(new AccountDeletedNotification($name, $initiatedByUser));
    }
}
