<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class DemoAccountGuard
{
    public function ensureProfileIsMutable(User $user): void
    {
        $this->assertMutable($user, 'profile', 'common.demo_account_profile_read_only');
    }

    public function ensureCanApply(User $user): void
    {
        $this->assertMutable($user, 'application', 'applications.demo_cannot_apply');
    }

    public function ensureCandidateActionsAreMutable(User $user): void
    {
        $this->assertMutable($user, 'candidate_action', 'common.demo_account_read_only');
    }

    /**
     * The field and message key differ per action so the error lands next to the
     * control the demo account cannot use.
     */
    private function assertMutable(User $user, string $field, string $messageKey): void
    {
        if (! $user->is_demo) {
            return;
        }

        throw ValidationException::withMessages([
            $field => __($messageKey),
        ]);
    }
}
