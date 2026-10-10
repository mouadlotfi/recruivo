<?php

namespace App\Support;

use App\Models\User;

/**
 * The single shape for the Profile/Preview page: the applicant payload plus the
 * label set. Owned here because the candidate's own preview and the admin's
 * candidate view render the same page and their payloads must not drift.
 */
final class CandidateProfilePreview
{
    /**
     * @return array<string, mixed>
     */
    public function applicant(User $user): array
    {
        $profile = $user->candidateProfile;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_summary' => $user->profile_summary,
            'candidateProfile' => $profile ? [
                'headline' => $profile->headline,
                'skills' => $profile->skills,
                'languages_data' => $profile->languages_data ?? [],
                'profile_links' => $profile->profile_links ?? [],
                'experiences' => $profile->experiences ?? [],
                'educations' => $profile->educations ?? [],
            ] : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function labels(string $titleKey, string $backKey): array
    {
        return [
            'recruiter_preview' => __($titleKey),
            'back_to_profile_settings' => __($backKey),
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
        ];
    }
}
