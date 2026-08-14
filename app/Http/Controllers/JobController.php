<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Services\SmartSearchService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::published()->with('company')->withSavedStateFor(auth()->user());

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

        $user = auth()->user();
        $hasFilters = $request->filled('search') || $request->filled('location') || $request->filled('category')
            || $request->filled('salary_min') || $request->filled('salary_max');
        $preferred = !$hasFilters && $user?->hasRole('Candidate')
            ? $user->candidateProfile?->preferred_categories
            : null;
        $hasPreferences = filled($preferred);

        $jobs = $query
            ->orderByPreference($preferred)
            ->paginate(12)
            ->withQueryString();

        if ($request->header('X-Infinite-Scroll') === '1') {
            return response()->json([
                'html' => view('jobs.partials.cards', compact('jobs'))->render(),
                'next_url' => $jobs->nextPageUrl(),
            ]);
        }

        return view('jobs.index', compact('jobs', 'hasPreferences'));
    }

    public function show(string $locale, Job $job)
    {
        abort_unless($job->isPubliclyVisible(), 404);

        $job->load('company');

        $user = auth()->user();

        if ($user?->hasRole('Candidate')) {
            $job->loadExists(['savedByCandidates as is_saved' => fn ($q) => $q->whereKey($user->id)]);
        }

        $includeSimilarJobs = !($user?->hasRole('Admin') || $user?->hasRole('Recruiter'));

        $similarJobs = $includeSimilarJobs
            ? Job::published()
                ->where('id', '!=', $job->id)
                ->when($job->category, fn ($builder) => $builder->where('category', $job->category))
                ->when($job->company_id, fn ($builder) => $builder->where('company_id', '!=', $job->company_id))
                ->with('company')
                ->withSavedStateFor(auth()->user())
                ->latest('published_at')
                ->take(4)
                ->get()
            : collect();

        $canApply = ($user?->hasRole('Candidate') ?? false) && !$user->is_demo;
        $isDemoCandidate = ($user?->hasRole('Candidate') ?? false) && $user->is_demo;
        $hasApplied = $canApply
            ? $user->applications()->where('job_id', $job->id)->exists()
            : false;

        $applicationSubmissionToken = null;
        if ($canApply && !$hasApplied) {
            $applicationSubmissionToken = (string) Str::uuid();
            request()->session()->put("job_application_submission.{$job->id}", [
                'token' => $applicationSubmissionToken,
                'completed' => false,
            ]);
        }

        return view('jobs.show', compact('job', 'similarJobs', 'canApply', 'hasApplied', 'isDemoCandidate', 'applicationSubmissionToken'));
    }

    public function search(Request $request, SmartSearchService $searchService)
    {
        $searchQuery = $searchService->normalize($request->input('search', ''));
        $filter = $request->input('filter', 'all'); // all, jobs, companies
        $remoteType = $request->input('remote_type');
        $location = $request->input('location');

        $hasCriteria = (bool) ($searchQuery || $remoteType || $location);
        $hasTextQuery = (bool) $searchQuery;

        $jobResults = collect();
        $companyResults = collect();

        // Show results if there's any search criteria
        if ($hasCriteria) {
            // Search jobs
            $jobResults = $searchQuery
                ? $searchService->jobs($searchQuery)
                : Job::published()->with('company')->latest('published_at')->get();
            $jobResults = $jobResults
                ->when($remoteType, fn ($items) => $items->where('remote_type', $remoteType))
                ->when($location, fn ($items) => $items->filter(fn ($job) => str_contains(mb_strtolower($job->location ?? ''), mb_strtolower($location))))
                ->values();

            // Search companies
            if ($hasTextQuery) {
                $companyResults = $searchService->companies($searchQuery)->values();
            }
        }

        $jobs = $hasCriteria && in_array($filter, ['all', 'jobs'])
            ? $this->paginateCollection($jobResults, 12, 'jobs_page', $request)
            : $this->paginateCollection(collect(), 12, 'jobs_page', $request);

        $companies = $hasTextQuery && in_array($filter, ['all', 'companies'])
            ? $this->paginateCollection($companyResults, 12, 'companies_page', $request)
            : $this->paginateCollection(collect(), 12, 'companies_page', $request);

        $jobsCount = $jobResults->count();
        $companiesCount = $companyResults->count();

        $suggestedCorrection = $searchQuery
            ? $searchService->suggestedCorrection($searchQuery, collect($jobs->items()), collect($companies->items()))
            : null;

        $popularSearches = $hasCriteria
            ? collect()
            : Job::published()
                ->whereNotNull('category')
                ->select('category')
                ->groupBy('category')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(6)
                ->pluck('category');

        $locations = Job::published()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        return view('search', compact(
            'jobs', 'companies', 'searchQuery', 'filter', 'remoteType', 'location',
            'suggestedCorrection', 'jobsCount', 'companiesCount', 'popularSearches', 'locations'
        ));
    }

    private function paginateCollection($items, int $perPage, string $pageName, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return (new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => $pageName]
        ))->withQueryString();
    }
}

