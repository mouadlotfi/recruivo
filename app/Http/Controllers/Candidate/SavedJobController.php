<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\DemoAccountGuard;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\ScrollMetadata;

class SavedJobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = $request->user()->savedJobs()
            ->published()
            ->with('company')
            ->withSavedStateFor($request->user())
            ->withExists(['applications as has_applied' => fn ($query) => $query->where('candidate_id', $request->user()->id)])
            ->latest('saved_jobs.created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Candidate/SavedJobs', [
            'jobs' => Inertia::scroll(
                fn () => $this->serializedScroll($jobs),
                metadata: fn () => ScrollMetadata::fromPaginator($jobs),
            ),
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'next_page_url' => $jobs->nextPageUrl(),
                'prev_page_url' => $jobs->previousPageUrl(),
            ],
            'labels' => [
                'saved_jobs' => __('jobs.saved_jobs'),
                'saved_jobs_empty_description' => __('jobs.saved_jobs_empty_description'),
                'browse_jobs' => __('jobs.browse_jobs'),
                'no_saved_jobs_yet' => __('jobs.no_saved_jobs_yet'),
                'show_more' => __('common.show_more'),
                'loading_more' => __('common.loading_more'),
                'load_more_failed' => __('common.load_more_failed'),
                'applied' => __('common.applied'),
                'save_job' => __('jobs.save_job'),
                'remove_saved_job' => __('jobs.remove_saved_job'),
                'demo_cannot_save_jobs' => __('jobs.demo_cannot_save_jobs'),
                'closing_soon' => __('jobs.closing_soon'),
                'remote' => __('jobs.remote'),
                'hybrid' => __('jobs.hybrid'),
                'onsite' => __('jobs.onsite'),
            ],
        ]);
    }

    /**
     * Serialize a paginator into the {data, meta} shape consumed by the
     * client's native InfiniteScroll component.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    private function serializedScroll(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => array_map(fn (Job $job) => $this->serializeJob($job), $paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeJob(Job $job): array
    {
        return [
            'id' => $job->id,
            'title' => $job->title,
            'location' => $job->location,
            'remote_type' => $job->remote_type,
            'category' => $job->category,
            'salary_min' => $job->salary_min,
            'salary_max' => $job->salary_max,
            'closes_at' => $job->closes_at?->toIso8601String(),
            'is_closing_soon' => $job->isClosingSoon(),
            'closes_label' => $job->closes_at
                ? __('jobs.closes_on', ['date' => $job->closes_at->translatedFormat('M j, Y')])
                : null,
            'is_saved' => (bool) ($job->is_saved ?? false),
            'has_applied' => (bool) ($job->has_applied ?? false),
            'published_at' => $job->published_at?->toIso8601String(),
            'company' => $job->company ? [
                'id' => $job->company->id,
                'name' => $job->company->name,
                'slug' => $job->company->slug,
                'logo_url' => $job->company->logo_url,
            ] : null,
        ];
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
