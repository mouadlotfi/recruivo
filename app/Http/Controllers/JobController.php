<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
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

        return view('jobs.index', compact('jobs'));
    }

    public function show(string $locale, Job $job)
    {
        abort_unless($job->status === JobStatus::Published, 404);

        $job->load('company');

        $user = auth()->user();
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

        return view('jobs.show', compact('job', 'similarJobs', 'canApply', 'hasApplied'));
    }

    public function search(Request $request)
    {
        $searchQuery = $request->input('search', '');
        $filter = $request->input('filter', 'all'); // all, jobs, companies
        $remoteType = $request->input('remote_type');
        $location = $request->input('location');

        // Initialize as empty paginators instead of collections
        $jobs = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => 'jobs_page',
        ]);
        
        $companies = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => 'companies_page',
        ]);

        // Show results if there's any search criteria
        if ($searchQuery || $remoteType || $location) {
            // Search jobs
            if (in_array($filter, ['all', 'jobs'])) {
                $jobsQuery = Job::published()->with('company');
                
                // Apply filters
                if ($searchQuery) {
                    $jobsQuery->where(function ($builder) use ($searchQuery) {
                        $builder->where('title', 'like', "%{$searchQuery}%")
                            ->orWhere('location', 'like', "%{$searchQuery}%")
                            ->orWhere('category', 'like', "%{$searchQuery}%")
                            ->orWhere('description', 'like', "%{$searchQuery}%")
                            ->orWhere('remote_type', 'like', "%{$searchQuery}%")
                            ->orWhereHas('company', function ($q) use ($searchQuery) {
                                $q->where('name', 'like', "%{$searchQuery}%");
                            });
                    });
                }
                
                if ($remoteType) {
                    $jobsQuery->where('remote_type', $remoteType);
                }

                if ($location) {
                    $jobsQuery->where('location', 'like', "%{$location}%");
                }
                
                $jobs = $jobsQuery
                    ->latest('published_at')
                    ->paginate(12, ['*'], 'jobs_page')
                    ->withQueryString();
            }

            // Search companies
            if (in_array($filter, ['all', 'companies']) && $searchQuery) {
                $companies = \App\Models\Company::where(function ($builder) use ($searchQuery) {
                        $builder->where('name', 'like', "%{$searchQuery}%")
                            ->orWhere('location', 'like', "%{$searchQuery}%")
                            ->orWhere('tagline', 'like', "%{$searchQuery}%")
                            ->orWhere('mission', 'like', "%{$searchQuery}%")
                            ->orWhere('culture', 'like', "%{$searchQuery}%");
                    })
                    ->withCount('jobs')
                    ->paginate(12, ['*'], 'companies_page')
                    ->withQueryString();
            }
        }

        return view('search', compact('jobs', 'companies', 'searchQuery', 'filter', 'remoteType', 'location'));
    }
}

