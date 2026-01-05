<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function store(Request $request, string $locale, Job $job)
    {
        $user = $request->user();

        // Check if user has already applied
        if ($user->applications()->where('job_id', $job->id)->exists()) {
            return redirect(localized_route('jobs.show', $job))->with('error', __('jobs.already_applied_error'));
        }

        // Check if user has a candidate profile with resume
        if (!$user->candidateProfile || !$user->candidateProfile->resume_path) {
            return redirect(localized_route('jobs.show', $job))->with('error', __('jobs.complete_profile_error'));
        }

        // Validate cover letter
        $validated = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:2000'],
        ]);

        $application = Application::create([
            'job_id' => $job->id,
            'candidate_id' => $user->id,
            'resume_path' => $user->candidateProfile->resume_path,
            'cover_letter' => $validated['cover_letter'] ?? null,
            'status' => ApplicationStatus::Pending,
            'original_status' => ApplicationStatus::Pending,
        ]);

        // Send notification to recruiter
        if ($job->company) {
            $job->company->recruiters()->each(function ($recruiter) use ($application) {
                $recruiter->notify(new \App\Notifications\NewApplicationNotification($application));
            });
        }

        return redirect(localized_route('jobs.show', $job))->with('success', __('jobs.application_submitted'));
    }
}

