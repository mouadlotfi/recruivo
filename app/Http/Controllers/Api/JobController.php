<?php

namespace App\Http\Controllers\Api;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\JobResource;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JobController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Job::published()->with('company');

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($location = $request->input('location')) {
            $query->where('location', 'like', "%{$location}%");
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($salaryMin = $request->input('salary_min')) {
            $query->where('salary_min', '>=', (int) $salaryMin);
        }

        if ($salaryMax = $request->input('salary_max')) {
            $query->where('salary_max', '<=', (int) $salaryMax);
        }

        $jobs = $query
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'total_roles' => Job::published()->count(),
            'remote_roles' => Job::published()->where('remote_type', 'remote')->count(),
            'new_this_week' => Job::published()->where('published_at', '>=', now()->subWeek())->count(),
            'active_companies' => Company::whereHas('jobs', fn ($q) => $q->published())->count(),
        ];

        $filters = array_filter(
            $request->only(['search', 'location', 'category', 'salary_min', 'salary_max', 'page']),
            fn ($value) => $value !== null && $value !== ''
        );

        $categories = Job::published()
            ->select('category')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return JobResource::collection($jobs)->additional([
            'filters' => $filters,
            'metrics' => $metrics,
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, Job $job): JobResource
    {
        abort_unless($job->status === JobStatus::Published, 404);

        $job->load('company');

        $user = $request->user('sanctum');
        $includeSimilarJobs = !($user?->hasRole('Admin') || $user?->hasRole('Recruiter'));

        $similarJobs = $includeSimilarJobs
            ? Job::published()
                ->where('id', '!=', $job->id)
                ->when($job->category, fn ($builder) => $builder->where('category', $job->category))
                ->when($job->company_id, fn ($builder) => $builder->where('company_id', '!=', $job->company_id))
                ->with('company')
                ->latest('published_at')
                ->take(4)
                ->get()
            : collect();

        $canApply = $user?->hasRole('Candidate') ?? false;
        $hasApplied = $canApply
            ? $user->applications()->where('job_id', $job->id)->exists()
            : false;

        return (new JobResource($job))->additional([
            'similar_jobs' => $includeSimilarJobs
                ? JobResource::collection($similarJobs)->resolve($request)
                : [],
            'can_apply' => $canApply,
            'has_applied' => $hasApplied,
            'apply_endpoint' => route('api.jobs.apply', ['jobId' => $job->id]),
        ]);
    }
}
