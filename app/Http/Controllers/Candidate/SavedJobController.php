<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\DemoAccountGuard;
use Illuminate\Http\Request;

class SavedJobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = $request->user()->savedJobs()
            ->published()
            ->with('company')
            ->withSavedStateFor($request->user())
            ->latest('saved_jobs.created_at')
            ->paginate(12)
            ->withQueryString();

        if ($request->header('X-Infinite-Scroll') === '1') {
            return response()->json([
                'html' => view('jobs.partials.cards', compact('jobs'))->render(),
                'next_url' => $jobs->nextPageUrl(),
            ]);
        }

        return view('candidate.saved-jobs', compact('jobs'));
    }

    public function store(Request $request, string $locale, Job $job, DemoAccountGuard $demoAccountGuard)
    {
        $demoAccountGuard->ensureCandidateActionsAreMutable($request->user());

        abort_unless($job->status === JobStatus::Published, 404);

        $request->user()->savedJobs()->syncWithoutDetaching([$job->id]);

        return back()->with('success', __('jobs.saved_job'));
    }

    public function destroy(Request $request, string $locale, Job $job, DemoAccountGuard $demoAccountGuard)
    {
        $demoAccountGuard->ensureCandidateActionsAreMutable($request->user());

        $request->user()->savedJobs()->detach($job->id);

        return back()->with('success', __('jobs.unsaved_job'));
    }
}
