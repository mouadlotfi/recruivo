<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Job;
use App\Notifications\NewApplicationNotification;
use App\Services\DemoAccountGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function store(Request $request, string $locale, Job $job, DemoAccountGuard $demoAccountGuard)
    {
        $user = $request->user();

        $demoAccountGuard->ensureCanApply($user);

        abort_unless($job->isPubliclyVisible(), 404);

        $submission = $request->session()->get("job_application_submission.{$job->id}");
        $submissionToken = (string) $request->input('submission_token', '');
        $isSameCompletedSubmission = is_array($submission)
            && ($submission['completed'] ?? false)
            && $submissionToken !== ''
            && hash_equals((string) ($submission['token'] ?? ''), $submissionToken);

        if ($user->applications()->where('job_id', $job->id)->exists()) {
            if ($isSameCompletedSubmission) {
                return redirect(localized_route('jobs.show', $job))->with('success', __('jobs.application_submitted'));
            }

            return redirect(localized_route('jobs.show', $job))->with('error', __('jobs.already_applied_error'));
        }

        $validated = $request->validate([
            'resume_source' => ['required', 'in:profile,upload'],
            'resume' => ['required_if:resume_source,upload', 'nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'cover_letter' => ['required', 'string', 'max:10000'],
        ]);
        $resumePath = $validated['resume_source'] === 'profile'
            ? $user->candidateProfile?->resume_path
            : null;

        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store(config('filesystems.application_resumes'), 'private');
        }

        if ($validated['resume_source'] === 'profile' && ! $resumePath) {
            return back()->withErrors(['resume_source' => __('jobs.profile_resume_missing')])->withInput();
        }

        $application = $user->applications()->firstOrCreate(
            ['job_id' => $job->id],
            [
                'resume_path' => $resumePath,
                'cover_letter' => $validated['cover_letter'],
                'status' => ApplicationStatus::Pending,
                'original_status' => ApplicationStatus::Pending,
            ]
        );

        if (! $application->wasRecentlyCreated) {
            if ($request->hasFile('resume')) {
                Storage::disk('private')->delete($resumePath);
            }
            if ($isSameCompletedSubmission) {
                return redirect(localized_route('jobs.show', $job))->with('success', __('jobs.application_submitted'));
            }

            return redirect(localized_route('jobs.show', $job))->with('error', __('jobs.already_applied_error'));
        }

        if (is_array($submission) && $submissionToken !== '' && hash_equals((string) ($submission['token'] ?? ''), $submissionToken)) {
            $request->session()->put("job_application_submission.{$job->id}.completed", true);
        }

        collect([$job->recruiter])
            ->merge($job->company?->recruiters ?? collect())
            ->filter()
            ->unique('id')
            ->each(function ($recruiter) use ($application) {
                $recruiter->notify(new NewApplicationNotification($application));
            });

        return redirect(localized_route('jobs.show', $job))->with('success', __('jobs.application_submitted'));
    }
}
