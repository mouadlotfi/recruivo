<?php

namespace App\Http\Controllers\Recruiter;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = $request->user()
            ->company
            ->jobs()
            ->withCount('applications')
            ->latest()
            ->paginate(10);

        return view('recruiter.jobs.index', compact('jobs'));
    }

    public function create()
    {
        return view('recruiter.jobs.create');
    }

    public function store(StoreJobRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;
        $data['recruiter_id'] = $request->user()->id;
        
        // Set status based on user selection
        $data['status'] = $data['status'] === 'published' ? JobStatus::Published : JobStatus::Draft;
        
        // Set published_at if status is published
        if ($data['status'] === JobStatus::Published) {
            $data['published_at'] = now();
        }

        $job = Job::create($data);

        return redirect()
            ->route('recruiter.jobs.index', ['locale' => app()->getLocale()])
            ->with('success', __('recruiter.job_created'));
    }

    public function show(string $locale, Job $job)
    {
        $this->authorize('view', $job);

        $job->loadCount('applications');

        return view('recruiter.jobs.show', compact('job'));
    }

    public function edit(string $locale, Job $job)
    {
        $this->authorize('update', $job);

        return view('recruiter.jobs.edit', compact('job'));
    }

    public function update(UpdateJobRequest $request, string $locale, Job $job)
    {
        $this->authorize('update', $job);

        $data = $request->validated();
        
        // Handle status changes
        if (isset($data['status'])) {
            $data['status'] = $data['status'] === 'published' ? JobStatus::Published : JobStatus::Draft;
            
            // Set published_at if status is being changed to published
            if ($data['status'] === JobStatus::Published && $job->status !== JobStatus::Published) {
                $data['published_at'] = now();
            }
        }

        $job->update($data);

        return redirect()
            ->route('recruiter.jobs.index', ['locale' => app()->getLocale()])
            ->with('success', __('recruiter.job_updated'));
    }

    public function destroy(string $locale, Job $job)
    {
        $this->authorize('delete', $job);

        $job->delete();

        return redirect()
            ->route('recruiter.jobs.index', ['locale' => app()->getLocale()])
            ->with('success', __('recruiter.job_deleted'));
    }

    public function toggle(string $locale, Job $job)
    {
        $this->authorize('update', $job);

        if ($job->status === JobStatus::Published) {
            $job->update([
                'status' => JobStatus::Draft,
                'published_at' => null,
            ]);
            $message = __('recruiter.job_unpublished');
        } else {
            $job->update([
                'status' => JobStatus::Published,
                'published_at' => now(),
            ]);
            $message = __('recruiter.job_published');
        }

        return back()->with('success', $message);
    }
}

