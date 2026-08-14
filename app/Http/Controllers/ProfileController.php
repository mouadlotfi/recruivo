<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\CandidateProfile;
use App\Services\DemoAccountGuard;
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

        $profileCompletion = $user->hasRole('Candidate') ? $user->profileCompletion() : null;

        return view('profile.edit', compact('user', 'profileCompletion'));
    }

    public function preview(Request $request)
    {
        $applicant = $request->user()->load('candidateProfile');

        return view('profile.preview', compact('applicant'));
    }

    public function update(UpdateProfileRequest $request, DemoAccountGuard $demoAccountGuard)
    {
        $user = $request->user();
        $demoAccountGuard->ensureProfileIsMutable($user);
        $data = $request->validated();

        if ($user->hasRole('Candidate')) {
            $user->update([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'profile_summary' => $data['profile_summary'] ?? null,
            ]);

            $profileData = collect($data)->only([
                'headline',
                'skills',
            ])->map(fn ($value) => $value === '' ? null : $value)->all();

            if (array_key_exists('languages', $data)) {
                $profileData['languages_data'] = collect($data['languages'])->map(fn ($language) => [
                    'language' => trim($language['language']),
                    'proficiency' => $language['proficiency'],
                ])->values()->all();
            }

            if (array_key_exists('links', $data)) {
                $profileData['profile_links'] = collect($data['links'])->map(fn ($link) => [
                    'name' => trim($link['name']),
                    'url' => trim($link['url']),
                ])->values()->all();
            }

            if (array_key_exists('experiences', $data)) {
                $profileData['experiences'] = collect($data['experiences'])->map(function ($experience) {
                    $current = (bool) $experience['is_current'];

                    return [
                        'job_title' => trim($experience['job_title']),
                        'company_name' => trim($experience['company_name']),
                        'location' => trim($experience['location'] ?? ''),
                        'start_date' => $experience['start_date'],
                        'end_date' => $current ? null : ($experience['end_date'] ?? null),
                        'is_current' => $current,
                        'description' => trim($experience['description'] ?? ''),
                    ];
                })->values()->all();
            }

            if (array_key_exists('educations', $data)) {
                $profileData['educations'] = collect($data['educations'])->map(function ($education) {
                    $current = (bool) $education['is_current'];

                    return [
                        'school' => trim($education['school']),
                        'degree' => trim($education['degree']),
                        'field_of_study' => trim($education['field_of_study']),
                        'start_date' => $education['start_date'],
                        'end_date' => $current ? null : ($education['end_date'] ?? null),
                        'is_current' => $current,
                        'description' => trim($education['description'] ?? ''),
                    ];
                })->values()->all();
            }

            if (array_key_exists('preferred_categories', $data)) {
                $profileData['preferred_categories'] = $data['preferred_categories'] ?? [];
            }

            if ($profileData !== []) {
                $user->candidateProfile()->updateOrCreate([], $profileData);
            }

            // Handle resume upload
            if ($request->hasFile('resume')) {
                $profile = $user->candidateProfile;

                // Delete old resume if exists
                if ($profile && $profile->resume_path) {
                    Storage::disk('private')->delete($profile->resume_path);
                }

                // Generate shorter filename: userid_timestamp.extension
                $extension = $request->file('resume')->getClientOriginalExtension();
                $filename = $user->id . '_' . time() . '.' . $extension;
                $resumePath = $request->file('resume')->storeAs('resumes', $filename, 'private');

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
            $user->company->update($companyData);

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

    public function saveQuickPreferences(Request $request, DemoAccountGuard $demoAccountGuard)
    {
        $user = $request->user();
        $demoAccountGuard->ensureProfileIsMutable($user);

        if (!$request->filled('skip')) {
            $data = $request->validate([
                'preferred_categories' => ['nullable', 'array', 'distinct'],
                'preferred_categories.*' => ['string', 'in:' . implode(',', \App\Enums\ItCategory::values())],
            ]);
            $user->candidateProfile()->updateOrCreate([], [
                'preferred_categories' => $data['preferred_categories'] ?? [],
            ]);
        }

        // The picker only shows after email verification; unverified users must
        // finish verifying first (keep the flag so the modal returns afterwards).
        if (!$user->hasVerifiedEmail()) {
            return redirect(localized_route('verification.notice'));
        }

        $request->session()->forget('show_preferences_picker');

        return redirect(localized_route('home'));
    }

    public function changePassword(ChangePasswordRequest $request, DemoAccountGuard $demoAccountGuard)
    {
        $user = $request->user();
        $demoAccountGuard->ensureProfileIsMutable($user);
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

    public function requestEmailChange(Request $request, DemoAccountGuard $demoAccountGuard)
    {
        $user = $request->user();
        $demoAccountGuard->ensureProfileIsMutable($user);

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

        app(DemoAccountGuard::class)->ensureProfileIsMutable($user);

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

