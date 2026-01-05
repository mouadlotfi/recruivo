<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;

trait FormatsAuthenticatedUsers
{
    protected function formatUserResponse(User $user): array
    {
        $user->loadMissing(['company', 'candidateProfile']);

        $personalEmail = $user->email;
        $companyEmail = $user->company?->email;

        $response = [
            'id' => $user->id,
            'name' => $user->isRecruiter() ? $user->display_name : $user->name,
            'email' => $personalEmail,
            'personal_email' => $personalEmail,
            'company_contact_email' => $companyEmail,
            'email_verified_at' => $user->email_verified_at,
            'company_id' => $user->company_id,
            'is_recruiter' => $user->is_recruiter,
            'roles' => $user->getRoleNames()->values(),
        ];

        if ($user->isRecruiter()) {
            // For recruiters, include company info and job title
            $response['job_title'] = $user->job_title;
            $response['company'] = $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'slug' => $user->company->slug,
                'email' => $user->company->email,
                'logo_url' => $user->company->logo_url,
                'tagline' => $user->company->tagline,
                'location' => $user->company->location,
                'website_url' => $user->company->website_url,
                'linkedin_url' => $user->company->linkedin_url,
                'size' => $user->company->size,
                'founded_year' => $user->company->founded_year,
                'mission' => $user->company->mission,
                'culture' => $user->company->culture,
            ] : null;
        } else {
            // For candidates, include personal info
            $response['location'] = $user->location;
            $response['phone'] = $user->phone;
            $response['profile_summary'] = $user->profile_summary;
            $response['profile_picture_url'] = $user->profile_picture_url;
            $response['resume_url'] = $user->resume_url;
            $response['company'] = null;
        }

        return $response;
    }
}
