<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\CandidateProfile;
use App\Services\UserAccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $user->load(['company', 'candidateProfile']);

        return view('profile.edit', compact('user'));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        if ($user->hasRole('Candidate')) {
            $user->update([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
            ]);

            // Handle resume upload
            if ($request->hasFile('resume')) {
                $profile = $user->candidateProfile;

                // Delete old resume if exists
                if ($profile && $profile->resume_path) {
                    Storage::disk('public')->delete($profile->resume_path);
                }

                // Generate shorter filename: userid_timestamp.extension
                $extension = $request->file('resume')->getClientOriginalExtension();
                $filename = $user->id . '_' . time() . '.' . $extension;
                $resumePath = $request->file('resume')->storeAs('resumes', $filename, 'public');

                if ($profile) {
                    $profile->update(['resume_path' => $resumePath]);
                } else {
                    CandidateProfile::create([
                        'user_id' => $user->id,
                        'resume_path' => $resumePath,
                    ]);
                }
            }
        } elseif ($user->hasRole('Recruiter') && $user->company) {
            // Update company profile
            $companyData = $data['company'] ?? [];
            $user->company->update(array_filter($companyData, fn ($value) => !is_null($value) && $value !== ''));

            // Handle logo upload
            if ($request->hasFile('logo')) {
                if ($user->company->logo_path) {
                    Storage::disk('public')->delete($user->company->logo_path);
                }

                $logoPath = $request->file('logo')->store('logos', 'public');
                $user->company->update(['logo_path' => $logoPath]);
            }
        }

        return back()->with('success', __('profile.profile_updated'));
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => __('auth.current_password_incorrect')]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        // Send notification
        $user->notify(new \App\Notifications\PasswordChangedNotification($user));

        return back()->with('success', __('profile.password_changed'));
    }

    public function requestEmailChange(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $oldEmail = $user->email;
        $newEmail = $validated['email'];

        // Generate verification token
        $token = \Illuminate\Support\Str::random(60);

        // Store pending email and token
        $user->update([
            'pending_email' => $newEmail,
            'email_verification_token' => \Illuminate\Support\Facades\Hash::make($token),
            'email_change_requested_at' => now(),
        ]);

        // Send verification email to NEW email address
        \Illuminate\Support\Facades\Notification::route('mail', $newEmail)
            ->notify(new \App\Notifications\EmailChangeVerificationNotification($user));

        return back()->with('success', __('profile.email_verification_sent'));
    }

    public function verifyEmailChange(Request $request, string $locale, $id, $hash)
    {
        $user = \App\Models\User::findOrFail($id);

        if (!$user->pending_email) {
            return redirect(localized_route('profile.edit'))->with('error', __('profile.no_pending_email'));
        }

        // Verify hash matches pending email
        if (!hash_equals($hash, sha1($user->pending_email))) {
            return redirect(localized_route('profile.edit'))->with('error', __('profile.invalid_verification_link'));
        }

        $oldEmail = $user->email;
        $newEmail = $user->pending_email;

        // Update email
        $user->update([
            'email' => $newEmail,
            'pending_email' => null,
            'email_verification_token' => null,
            'email_change_requested_at' => null,
            'email_verified_at' => now(),
        ]);

        // Send confirmation to old email
        \Illuminate\Support\Facades\Notification::route('mail', $oldEmail)
            ->notify(new \App\Notifications\EmailChangedNotification($user, $oldEmail));

        return redirect(localized_route('profile.edit'))->with('success', __('profile.email_updated'));
    }

    public function destroy(Request $request, string $locale, UserAccountDeletionService $deletionService)
    {
        $user = $request->user();

        $deletionService->deleteUserAccount($user);

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(localized_route('home'))->with('success', __('profile.account_deleted'));
    }
}

