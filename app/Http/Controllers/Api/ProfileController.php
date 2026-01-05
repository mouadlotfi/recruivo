<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsAuthenticatedUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\CandidateProfile;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\EmailChangedNotification;
use App\Notifications\EmailChangeVerificationNotification;
use App\Services\UserAccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class ProfileController extends Controller
{
    use FormatsAuthenticatedUsers;

    public function __construct(protected UserAccountDeletionService $userAccountDeletionService)
    {
    }

    /**
     * Update the user's profile information.
     */
    public function update(UpdateProfileRequest $request)
    {
        \Log::info('Profile update request received', [
            'user_id' => Auth::id(),
            'data' => $request->all(),
            'files' => $request->allFiles()
        ]);

        $user = Auth::user();
        $data = $request->validated();

        \Log::info('Validated data', ['data' => $data]);

        // Handle resume upload for candidates
        if ($request->hasFile('resume') && $user->hasRole('Candidate')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
            
            // Update or create candidate profile
            $candidateProfile = $user->candidateProfile;
            if ($candidateProfile) {
                // Delete old resume if exists
                if ($candidateProfile->resume_path) {
                    Storage::disk('public')->delete($candidateProfile->resume_path);
                }
                $candidateProfile->update(['resume_path' => $resumePath]);
            } else {
                CandidateProfile::create([
                    'user_id' => $user->id,
                    'resume_path' => $resumePath,
                ]);
            }
        }

        // Check if email is being changed
        $oldEmail = $user->email;
        $emailChanged = isset($data['email']) && $data['email'] !== $oldEmail;

        // Remove file fields from user data
        unset($data['resume']);

        if ($emailChanged) {
            $data['email_verified_at'] = null;
        }

        \Log::info('Updating user with data', ['user_id' => $user->id, 'data' => $data]);

        $user->update($data);
        $user->refresh();

        \Log::info('User updated successfully', ['user_id' => $user->id]);

        // Send email change notification if email was changed
        if ($emailChanged) {
            // Send notification to old email about the change
            Notification::route('mail', $oldEmail)
                ->notify(new EmailChangedNotification($user, $oldEmail));

            // Send verification email to new email
            $user->notify(new EmailChangeVerificationNotification($user));
        }

        $response = [
            'message' => 'Profile updated successfully',
            'data' => $this->formatUserResponse($user),
        ];

        \Log::info('Profile update response', [
            'response' => $response,
            'email_changed' => $emailChanged,
            'user_email_verified_at' => $user->email_verified_at
        ]);

        return response()->json($response);
    }

    /**
     * Change the user's password.
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = Auth::user();
        
        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors' => [
                    'current_password' => ['The provided password does not match our records.'],
                ],
            ], 422);
        }
        
        // Update password
        $user->update([
            'password' => Hash::make($request->password)
        ]);
        
        // Send password change notification
        $user->notify(new PasswordChangedNotification($user));
        
        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        $this->userAccountDeletionService->delete($user, true);

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}
