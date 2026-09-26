<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class DemoAccountGuard
{
    public function ensureProfileIsMutable(User $user): void
    {
        if (! $user->is_demo) {
            return;
        }

        throw ValidationException::withMessages([
            'profile' => __('common.demo_account_profile_read_only'),
        ]);
    }

    public function ensureCanApply(User $user): void
    {
        if (! $user->is_demo) {
            return;
        }

        throw ValidationException::withMessages([
            'application' => __('applications.demo_cannot_apply'),
        ]);
    }

    public function ensureCandidateActionsAreMutable(User $user): void
    {
        if (! $user->is_demo) {
            return;
        }

        throw ValidationException::withMessages([
            'candidate_action' => __('common.demo_account_read_only'),
        ]);
    }
}
