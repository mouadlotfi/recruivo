<?php

namespace App\Http\Controllers;

use App\Enums\ItCategory;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\CandidateProfile;
use App\Models\User;
use App\Notifications\EmailChangedNotification;
use App\Notifications\EmailChangeVerificationNotification;
use App\Notifications\PasswordChangedNotification;
use App\Services\DemoAccountGuard;
use App\Services\UserAccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const EDIT_PAGE_LABEL_KEYS = [
        'title', 'profile_settings', 'company_profile', 'profile_information',
        'preview_as_recruiter', 'full_name', 'phone_number', 'phone_placeholder',
        'about', 'about_placeholder', 'about_help', 'professional_headline',
        'headline_placeholder', 'skills', 'skills_placeholder', 'job_interests',
        'job_interests_help', 'resume', 'resume_uploaded', 'resume_uploaded_help',
        'replace_resume', 'view_resume', 'choose_file', 'no_file_chosen',
        'resume_formats', 'company_name', 'tagline', 'location', 'website_url',
        'website_placeholder', 'linkedin_url', 'linkedin_placeholder', 'company_logo',
        'choose_logo', 'logo_formats', 'mission', 'culture', 'update_profile',
        'language', 'language_help', 'language_en', 'language_fr', 'change_email_address',
        'pending_email_change', 'check_new_email_inbox', 'current_email',
        'new_email_address', 'verification_email_sent', 'request_email_change',
        'change_password', 'current_password', 'new_password', 'confirm_new_password',
        'delete_account', 'delete_account_warning', 'delete_account_confirmation',
        'profile_completion', 'profile_completion_help', 'profile_complete',
        'profile_completion_steps_left', 'languages', 'languages_help', 'add_language',
        'select_language', 'language_name', 'proficiency_level', 'save_entry', 'edit_entry',
        'remove_entry', 'cancel', 'no_languages', 'proficiency_beginner',
        'proficiency_elementary', 'proficiency_intermediate', 'proficiency_professional_working',
        'proficiency_fluent', 'proficiency_native_bilingual', 'links', 'links_help',
        'add_link', 'no_links', 'select_link_type', 'link_name', 'link_url', 'links_used',
        'link_type_unique', 'links_max', 'personal_website', 'experience', 'experience_help',
        'add_experience', 'no_experience', 'job_title', 'company_name_entry', 'start_date',
        'end_date', 'currently_work_here', 'description_responsibilities', 'present',
        'education', 'education_help', 'add_education', 'no_education', 'school', 'degree',
        'field_of_study', 'currently_studying_here', 'additional_information', 'optional',
        'education_description_optional_help', 'end_date_after_start',
        'complete_required_fields', 'year_cannot_be_future',
    ];

    public function edit()
    {
        $user = auth()->user();
        $user->load(['company', 'candidateProfile']);

        $profileCompletion = $user->hasRole('Candidate') ? $user->profileCompletion() : null;

        if ($profileCompletion !== null) {
            $profileCompletion['steps_label'] = trans_choice(
                'profile.profile_completion_steps_left',
                count($profileCompletion['missing']),
                ['count' => count($profileCompletion['missing'])],
            );
        }

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_summary' => $user->profile_summary,
                'pending_email' => $user->pending_email,
                'is_demo' => (bool) $user->is_demo,
                'roles' => $user->getRoleNames()->values()->all(),
            ],
            'candidateProfile' => $user->candidateProfile ? [
                'headline' => $user->candidateProfile->headline,
                'skills' => $user->candidateProfile->skills,
                'languages_data' => $user->candidateProfile->languages_data ?? [],
                'profile_links' => $user->candidateProfile->profile_links ?? [],
                'experiences' => $user->candidateProfile->experiences ?? [],
                'educations' => $user->candidateProfile->educations ?? [],
                'preferred_categories' => $user->candidateProfile->preferred_categories ?? [],
                'resume_path' => $user->candidateProfile->resume_path,
                'resume_url' => $user->candidateProfile->resume_path
                    ? localized_route('candidate.resume.view')
                    : null,
            ] : null,
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'tagline' => $user->company->tagline,
                'location' => $user->company->location,
                'website_url' => $user->company->website_url,
                'linkedin_url' => $user->company->linkedin_url,
                'mission' => $user->company->mission,
                'culture' => $user->company->culture,
                'logo_url' => $user->company->logo_url,
            ] : null,
            'profileCompletion' => $profileCompletion,
            'categories' => ItCategory::values(),
            'languages' => [
                'Arabic', 'Chinese', 'Dutch', 'English', 'French', 'German', 'Hindi',
                'Italian', 'Japanese', 'Korean', 'Portuguese', 'Russian', 'Spanish', 'Turkish',
            ],
            'linkTypes' => ['LinkedIn', 'X', 'GitHub', 'Personal Website', 'Instagram'],
            'labels' => collect(self::EDIT_PAGE_LABEL_KEYS)->mapWithKeys(
                fn (string $key) => [$key => __("profile.$key")],
            )->all() + [
                'demo_read_only' => __('common.demo_account_profile_read_only'),
                'demo_read_only_description' => __('common.demo_account_profile_read_only_description'),
                'cancel_common' => __('common.cancel'),
            ],
        ]);
    }

    public function preview(Request $request)
    {
        $applicant = $request->user()->load('candidateProfile');

        return Inertia::render('Profile/Preview', [
            'applicant' => [
                'name' => $applicant->name,
                'email' => $applicant->email,
                'phone' => $applicant->phone,
                'profile_summary' => $applicant->profile_summary,
                'candidateProfile' => $applicant->candidateProfile ? [
                    'headline' => $applicant->candidateProfile->headline,
                    'skills' => $applicant->candidateProfile->skills,
                    'languages_data' => $applicant->candidateProfile->languages_data ?? [],
                    'profile_links' => $applicant->candidateProfile->profile_links ?? [],
                    'experiences' => $applicant->candidateProfile->experiences ?? [],
                    'educations' => $applicant->candidateProfile->educations ?? [],
                ] : null,
            ],
            'labels' => [
                'recruiter_preview' => __('profile.recruiter_preview'),
                'back_to_profile_settings' => __('profile.back_to_profile_settings'),
                'email_address' => __('profile.email_address'),
                'phone_number' => __('profile.phone_number'),
                'about' => __('profile.about'),
                'present' => __('profile.present'),
                'proficiency_beginner' => __('profile.proficiency_beginner'),
                'proficiency_elementary' => __('profile.proficiency_elementary'),
                'proficiency_intermediate' => __('profile.proficiency_intermediate'),
                'proficiency_professional_working' => __('profile.proficiency_professional_working'),
                'proficiency_fluent' => __('profile.proficiency_fluent'),
                'proficiency_native_bilingual' => __('profile.proficiency_native_bilingual'),
                'about_heading' => __('profile.about'),
                'contact_information' => __('recruiter.contact_information'),
                'not_provided' => __('recruiter.not_provided'),
                'skills' => __('recruiter.skills'),
                'languages' => __('recruiter.languages'),
                'links' => __('profile.links'),
                'experience' => __('recruiter.experience'),
                'education' => __('recruiter.education'),
            ],
        ]);
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
                $filename = $user->id.'_'.time().'.'.$extension;
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

        if (! $request->filled('skip')) {
            $data = $request->validate([
                'preferred_categories' => ['nullable', 'array', 'distinct'],
                'preferred_categories.*' => ['string', 'in:'.implode(',', ItCategory::values())],
            ]);
            $user->candidateProfile()->updateOrCreate([], [
                'preferred_categories' => $data['preferred_categories'] ?? [],
            ]);
        }

        // The picker only shows after email verification; unverified users must
        // finish verifying first (keep the flag so the modal returns afterwards).
        if (! $user->hasVerifiedEmail()) {
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

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => __('auth.current_password_incorrect')]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        // Send notification
        $user->notify(new PasswordChangedNotification($user));

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
        $token = Str::random(60);

        // Store pending email and token
        $user->update([
            'pending_email' => $newEmail,
            'email_verification_token' => Hash::make($token),
            'email_change_requested_at' => now(),
        ]);

        // Send verification email to NEW email address
        Notification::route('mail', $newEmail)
            ->notify(new EmailChangeVerificationNotification($user));

        return back()->with('success', __('profile.email_verification_sent'));
    }

    public function verifyEmailChange(Request $request, string $locale, $id, $hash)
    {
        $user = User::findOrFail($id);

        app(DemoAccountGuard::class)->ensureProfileIsMutable($user);

        if (! $user->pending_email) {
            return redirect(localized_route('profile.edit'))->with('error', __('profile.no_pending_email'));
        }

        // Verify hash matches pending email
        if (! hash_equals($hash, sha1($user->pending_email))) {
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
        Notification::route('mail', $oldEmail)
            ->notify(new EmailChangedNotification($user, $oldEmail));

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
