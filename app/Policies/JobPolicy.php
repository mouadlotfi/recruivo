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
        // Recruiters can view their own company's jobs
        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $job->company_id;
        }

        // Admins can view any job
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Candidates can view published jobs
        return $job->status->value === 'published';
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
        // Only recruiters from the same company can update
        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $job->company_id;
        }

        // Admins can update any job
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can delete the job.
     */
    public function delete(User $user, Job $job): bool
    {
        // Only recruiters from the same company can delete
        if ($user->hasRole('Recruiter')) {
            return $user->company_id === $job->company_id;
        }

        // Admins can delete any job
        return $user->hasRole('Admin');
    }
}

