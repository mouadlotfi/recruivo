<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /**
     * Determine whether the user can view the application.
     */
    public function view(User $user, Application $application): bool
    {
        if ($user->hasRole('Candidate')) {
            return $user->id === $application->candidate_id;
        }

        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $application->job->company_id;
        }

        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can update the application.
     */
    public function update(User $user, Application $application): bool
    {
        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $application->job->company_id;
        }

        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the candidate can withdraw their own application.
     */
    public function withdraw(User $user, Application $application): bool
    {
        return $user->hasRole('Candidate')
            && $user->id === $application->candidate_id
            && in_array($application->status, [
                ApplicationStatus::Pending,
                ApplicationStatus::Shortlisted,
                ApplicationStatus::Interview,
            ], true);
    }

    /**
     * Determine whether the user can delete the application.
     */
    public function delete(User $user, Application $application): bool
    {
        if ($user->hasRole('Candidate')) {
            return $user->id === $application->candidate_id;
        }

        return $user->hasRole('Admin');
    }
}

