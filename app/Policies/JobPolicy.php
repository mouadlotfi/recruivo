<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    /**
     * Determine whether the user can view the job.
     */
    public function view(User $user, Job $job): bool
    {
        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $job->company_id;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        return $job->isPubliclyVisible();
    }

    /**
     * Determine whether the user can create jobs.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Recruiter') && $user->company_id;
    }

    /**
     * Determine whether the user can update the job.
     */
    public function update(User $user, Job $job): bool
    {
        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $job->company_id;
        }

        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can delete the job.
     */
    public function delete(User $user, Job $job): bool
    {
        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $job->company_id;
        }

        return $user->hasRole('Admin');
    }
}
