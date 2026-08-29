<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsAuthenticatedUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\CandidateProfile;
use App\Notifications\EmailChangeVerificationNotification;
use App\Notifications\PasswordChangedNotification;
use App\Services\DemoAccountGuard;
use App\Services\UserAccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use FormatsAuthenticatedUsers;

    public function __construct(protected UserAccountDeletionService $userAccountDeletionService) {}

    /**
     * Update the user's profile information.
     */
    public function update(UpdateProfileRequest $request, DemoAccountGuard $demoAccountGuard)
    {
        $user = Auth::user();
        $demoAccountGuard->ensureProfileIsMutable($user);
        $data = $request->validated();

        if ($request->hasFile('resume') && $user->hasRole('Candidate')) {
            $resumePath = $request->file('resume')->store('resumes', 'private');
            $candidateProfile = $user->candidateProfile;
            if ($candidateProfile) {
                if ($candidateProfile->resume_path) {
                    Storage::disk('private')->delete($candidateProfile->resume_path);
                }
                $candidateProfile->update(['resume_path' => $resumePath]);
            } else {
                CandidateProfile::create([
                    'user_id' => $user->id,
                    'resume_path' => $resumePath,
                ]);
            }
        }

        $requestedEmail = $data['email'] ?? null;
        $emailChanged = $requestedEmail && $requestedEmail !== $user->email;

        // Email and role-specific nested data are handled by dedicated flows.
        unset($data['resume'], $data['email'], $data['company'], $data['logo']);

        if ($emailChanged) {
            $user->update([
                'pending_email' => $requestedEmail,
                'email_change_requested_at' => now(),
            ]);
        }

        $user->update($data);
        $user->refresh();

        if ($emailChanged) {
            Notification::route('mail', $requestedEmail)
                ->notify(new EmailChangeVerificationNotification($user));
        }

        $response = [
            'message' => $emailChanged
                ? 'Profile updated. Check your new email address to confirm the change.'
                : 'Profile updated successfully',
            'data' => $this->formatUserResponse($user),
        ];

        return response()->json($response);
    }

    /**
     * Change the user's password.
     */
    public function changePassword(ChangePasswordRequest $request, DemoAccountGuard $demoAccountGuard)
    {
        $user = Auth::user();
        $demoAccountGuard->ensureProfileIsMutable($user);

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors' => [
                    'current_password' => ['The provided password does not match our records.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $user->notify(new PasswordChangedNotification($user));

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        $this->userAccountDeletionService->deleteUserAccount($user, true);

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}
