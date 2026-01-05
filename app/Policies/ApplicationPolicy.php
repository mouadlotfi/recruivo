<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /**
     * Determine whether the user can view the application.
     */
    public function view(User $user, Application $application): bool
    {
        // Candidates can view their own applications
        if ($user->hasRole('Candidate')) {
            return $user->id === $application->candidate_id;
        }

        // Recruiters can view applications for their company's jobs
        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $application->job->company_id;
        }

        // Admins can view any application
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can update the application.
     */
    public function update(User $user, Application $application): bool
    {
        // Only recruiters from the same company can update application status
        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $application->job->company_id;
        }

        // Admins can update any application
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can delete the application.
     */
    public function delete(User $user, Application $application): bool
    {
        // Candidates can delete their own applications
        if ($user->hasRole('Candidate')) {
            return $user->id === $application->candidate_id;
        }

        // Admins can delete any application
        return $user->hasRole('Admin');
    }
}

