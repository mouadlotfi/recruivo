<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{

    public function index(Request $request, string $locale, Job $job)
    {
        $this->authorize('view', $job);

        $status = $request->query('status', 'all');
        $allowedStatuses = array_map(fn (ApplicationStatus $status) => $status->value, ApplicationStatus::cases());

        if ($status !== 'all' && !in_array($status, $allowedStatuses, true)) {
            return redirect(localized_route('recruiter.jobs.applications', $job));
        }

        $statusCounts = $job->applications()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $applications = $job->applications()
            ->with(['candidate.candidateProfile', 'statusEvents.changedBy:id,name'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $noteTemplates = $request->user()->noteTemplates()->orderBy('name')->get();

        return view('recruiter.applications.index', compact('job', 'applications', 'status', 'statusCounts', 'noteTemplates'));
    }

    public function update(UpdateApplicationStatusRequest $request, string $locale, Application $application)
    {
        $this->authorize('update', $application);

        $previousStatus = $application->status;
        $application->applyStatusUpdate($request->validated());

        // Send notification if status changed
        if ($previousStatus != $application->status) {
            $application->candidate->notify(
                new \App\Notifications\ApplicationStatusUpdatedNotification($application)
            );
        }

        return back()->with('success', __('recruiter.application_updated'));
    }

    public function downloadResume(string $locale, Application $application)
    {
        $this->authorize('view', $application);

        $resumePath = $application->resume_path ?: $application->candidate->candidateProfile?->resume_path;

        if (!$resumePath || !Storage::disk('private')->exists($resumePath)) {
            return back()->with('error', __('recruiter.resume_not_found'));
        }

        return response()->file(Storage::disk('private')->path($resumePath), [
            'Content-Disposition' => 'inline; filename="'.basename($resumePath).'"',
        ]);
    }
}

