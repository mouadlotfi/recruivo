<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use App\Services\SmartSearchService;
use App\Support\JobDescriptionFormatter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\ScrollMetadata;

class JobController extends Controller
{
    /**
     * Page-string keys for the public jobs index (jobs.*), resolved in the
     * current locale and passed as a flat `labels` prop — page strings
     * travel with the page, the shared shell translations stay lean.
     *
     * @var array<int, string>
     */
    private const INDEX_PAGE_LABEL_KEYS = [
        'find_opportunity', 'discover_jobs', 'recruiter_explore_title', 'recruiter_explore_subtitle',
        'recommended_for_you', 'search_jobs', 'location', 'category', 'min_salary', 'max_salary',
        'clear_filters', 'no_jobs_found', 'check_back_later', 'back_to_home',
        'remote', 'hybrid', 'onsite', 'closing_soon', 'save_job', 'remove_saved_job',
        'demo_cannot_save_jobs',
    ];

    /**
     * Page-string keys for the public job detail page (jobs.*).
     *
     * @var array<int, string>
     */
    private const SHOW_PAGE_LABEL_KEYS = [
        'back_to_jobs', 'job_details', 'full_description', 'apply_now_button', 'you_have_applied',
        'cover_letter', 'write_cover_letter', 'cover_letter_placeholder', 'resume_source',
        'use_profile_resume', 'upload_application_resume', 'application_resume',
        'application_resume_help', 'only_candidates_can_apply', 'log_in_to_apply',
        'closing_soon', 'view_company_profile', 'similar_jobs_title', 'save_job',
        'remove_saved_job', 'demo_cannot_save_jobs', 'remote', 'hybrid', 'onsite',
    ];

    /**
     * Page-string keys for the search page (common.*), resolved in the
     * current locale and passed as a flat `labels` prop.
     *
     * @var array<int, string>
     */
    private const SEARCH_PAGE_LABEL_KEYS = [
        'search', 'search_placeholder', 'clear_search', 'search_all_results', 'search_error',
        'all', 'jobs', 'companies', 'filter', 'filter_location', 'filter_work_type',
        'remote', 'hybrid', 'onsite', 'apply_filters', 'clear_filters', 'results_for_query',
        'showing_results', 'did_you_mean', 'popular_searches', 'try_popular_search',
        'refine_filters', 'remove_filter', 'jobs_count', 'companies_count', 'no_results',
        'no_jobs_match', 'no_companies_match', 'broaden_search', 'edit_search',
        'start_search', 'search_jobs_and_companies', 'all_types', 'work_type', 'location',
        'show_more', 'loading_more', 'load_more_failed',
    ];

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
        $preferred = ! $hasFilters && $user?->hasRole('Candidate')
            ? $user->candidateProfile?->preferred_categories
            : null;
        $hasPreferences = filled($preferred);

        if ($user?->hasRole('Candidate')) {
            $query->withExists(['applications as has_applied' => fn ($q) => $q->where('candidate_id', $user->id)]);
        }

        $jobs = $query
            ->orderByPreference($preferred)
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Jobs/Index', [
            'jobs' => Inertia::scroll(
                fn () => $this->serializedScroll($jobs),
                metadata: fn () => ScrollMetadata::fromPaginator($jobs),
            ),
            'hasPreferences' => $hasPreferences,
            'filters' => [
                'search' => $request->input('search'),
                'location' => $request->input('location'),
                'category' => $request->input('category'),
                'salary_min' => $request->input('salary_min'),
                'salary_max' => $request->input('salary_max'),
            ],
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'next_page_url' => $jobs->nextPageUrl(),
                'prev_page_url' => $jobs->previousPageUrl(),
            ],
            'labels' => [
                ...collect(self::INDEX_PAGE_LABEL_KEYS)->mapWithKeys(
                    fn (string $key) => [$key => __("jobs.$key")]
                )->all(),
                'apply_filters' => __('common.apply_filters'),
                'applied' => __('common.applied'),
                'show_more' => __('common.show_more'),
                'loading_more' => __('common.loading_more'),
                'load_more_failed' => __('common.load_more_failed'),
            ],
        ]);
    }

    public function show(string $locale, Job $job)
    {
        abort_unless($job->isPubliclyVisible(), 404);

        $job->load('company');

        $user = auth()->user();

        if ($user?->hasRole('Candidate')) {
            $job->loadExists(['savedByCandidates as is_saved' => fn ($q) => $q->whereKey($user->id)]);
        }

        $includeSimilarJobs = ! ($user?->hasRole('Admin') || $user?->hasRole('Recruiter'));

        $similarJobs = $includeSimilarJobs
            ? Job::published()
                ->where('id', '!=', $job->id)
                ->when($job->category, fn ($builder) => $builder->where('category', $job->category))
                ->when($job->company_id, fn ($builder) => $builder->where('company_id', '!=', $job->company_id))
                ->with('company')
                ->withSavedStateFor(auth()->user())
                ->when(
                    $user?->hasRole('Candidate'),
                    fn ($builder) => $builder->withExists(['applications as has_applied' => fn ($q) => $q->where('candidate_id', $user->id)])
                )
                ->latest('published_at')
                ->take(4)
                ->get()
            : collect();

        $canApply = ($user?->hasRole('Candidate') ?? false) && ! $user->is_demo;
        $isDemoCandidate = ($user?->hasRole('Candidate') ?? false) && $user->is_demo;
        $hasApplied = $canApply
            ? $user->applications()->where('job_id', $job->id)->exists()
            : false;

        $applicationSubmissionToken = null;
        if ($canApply && ! $hasApplied) {
            $applicationSubmissionToken = (string) Str::uuid();
            request()->session()->put("job_application_submission.{$job->id}", [
                'token' => $applicationSubmissionToken,
                'completed' => false,
            ]);
        }

        return Inertia::render('Jobs/Show', [
            'job' => $this->serializeJobDetail($job),
            'similarJobs' => $similarJobs
                ->map(fn (Job $similarJob) => $this->serializeJobCard($similarJob))
                ->values()
                ->all(),
            'canApply' => $canApply,
            'hasApplied' => $hasApplied,
            'isDemoCandidate' => $isDemoCandidate,
            'hasProfileResume' => (bool) ($user?->candidateProfile?->resume_path),
            'applicationSubmissionToken' => $applicationSubmissionToken,
            'labels' => [
                ...collect(self::SHOW_PAGE_LABEL_KEYS)->mapWithKeys(
                    fn (string $key) => [$key => __("jobs.$key")]
                )->all(),
                'demo_cannot_apply' => __('applications.demo_cannot_apply'),
                'about_company' => $job->company
                    ? __('jobs.about_company', ['company' => $job->company->name])
                    : '',
                'meta_description' => $job->company
                    ? __('jobs.meta_description_with_company', ['title' => $job->title, 'company' => $job->company->name])
                    : $job->title,
                'expand' => __('common.expand'),
                'cancel' => __('common.cancel'),
                'done' => __('common.done'),
                'close' => __('common.close'),
                'loading' => __('common.loading'),
            ],
        ]);
    }

    /**
     * Flat serialization of a job for cards (index + similar jobs) — no
     * Eloquent models leak into props. `has_applied`/`is_saved` come from
     * withExists subqueries (false for guests and non-candidates).
     *
     * @return array<string, mixed>
     */
    private function serializeJobCard(Job $job): array
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

    /**
     * Full serialization for the job detail page. The description travels as
     * `description_html` — output of App\Support\JobDescriptionFormatter,
     * which escapes every user-controlled line, so the Vue page may render
     * it with v-html (the only v-html on that page).
     *
     * @return array<string, mixed>
     */
    private function serializeJobDetail(Job $job): array
    {
        $card = $this->serializeJobCard($job);

        return [
            ...$card,
            'description_html' => JobDescriptionFormatter::format((string) $job->description),
            'posted_label' => $job->published_at
                ? __('jobs.posted_time_ago', ['time' => $job->published_at->diffForHumans()])
                : __('jobs.posted_recently'),
            'company' => $job->company ? [
                'id' => $job->company->id,
                'name' => $job->company->name,
                'slug' => $job->company->slug,
                'logo_url' => $job->company->logo_url,
                'tagline' => $job->company->tagline,
                'mission' => $job->company->mission,
                'culture' => $job->company->culture,
                'founded_year' => $job->company->founded_year,
                'website_url' => $job->company->website_url,
                'linkedin_url' => $job->company->linkedin_url,
            ] : null,
        ];
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
            // Search jobs. Keyword search ranks by relevance in PHP (the
            // scoring cannot be expressed in SQL), but remote_type/location
            // are pushed down into the query so we don't materialize and then
            // discard rows in PHP.
            $searchUser = auth()->user();
            $jobResults = $searchQuery
                ? $searchService->jobs($searchQuery, $remoteType, $location)
                : Job::published()
                    ->with('company')
                    ->withSavedStateFor($searchUser)
                    ->when($remoteType, fn ($builder) => $builder->where('remote_type', $remoteType))
                    ->when($location, fn ($builder) => $builder->where('location', 'like', '%'.$location.'%'))
                    ->when(
                        $searchUser?->hasRole('Candidate'),
                        fn ($builder) => $builder->withExists(['applications as has_applied' => fn ($q) => $q->where('candidate_id', $searchUser->id)])
                    )
                    ->latest('published_at')
                    ->get();

            // Search companies (keyword only — location already filtered in SQL).
            if ($hasTextQuery) {
                $companyResults = $searchService->companies($searchQuery, $location)->values();
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

        return Inertia::render('Search/Index', [
            'searchQuery' => $searchQuery,
            'filter' => $filter,
            'remoteType' => $remoteType,
            'location' => $location,
            'jobs' => array_map(fn (Job $job) => $this->serializeJobCard($job), $jobs->items()),
            'companies' => $companies->getCollection()
                ->map(fn (Company $company) => $this->serializeSearchCompany($company))
                ->values()
                ->all(),
            'jobsCount' => $jobsCount,
            'companiesCount' => $companiesCount,
            'totalCount' => $jobsCount + $companiesCount,
            'suggestedCorrection' => $suggestedCorrection,
            'popularSearches' => $popularSearches->values()->all(),
            'locations' => $locations->values()->all(),
            'jobsPagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'next_page_url' => $jobs->nextPageUrl(),
                'prev_page_url' => $jobs->previousPageUrl(),
            ],
            'companiesPagination' => [
                'total' => $companies->total(),
                'per_page' => $companies->perPage(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'next_page_url' => $companies->nextPageUrl(),
                'prev_page_url' => $companies->previousPageUrl(),
            ],
            'labels' => [
                ...collect(self::SEARCH_PAGE_LABEL_KEYS)->mapWithKeys(
                    fn (string $key) => [$key => __("common.$key")]
                )->all(),
                'applied' => __('common.applied'),
                'save_job' => __('jobs.save_job'),
                'remove_saved_job' => __('jobs.remove_saved_job'),
                'demo_cannot_save_jobs' => __('jobs.demo_cannot_save_jobs'),
                'closing_soon' => __('jobs.closing_soon'),
                'all_locations' => __('jobs.all_locations'),
                'latest_jobs' => __('companies.latest_jobs'),
                'jobs_title' => __('jobs.title'),
                'companies_title' => __('companies.title'),
                'no_results_title' => __('common.no_results'),
                'no_results_message' => __('common.broaden_search'),
            ],
        ]);
    }

    /**
     * Flat serialization of a company for the search grid — same shape as
     * CompanyController::serializeCompanyCard so Components/Companies/
     * CompanyCard.vue renders it. Search results don't carry latest_jobs;
     * jobs_count comes from SmartSearchService's withCount('jobs').
     *
     * @return array<string, mixed>
     */
    private function serializeSearchCompany(Company $company): array
    {
        $jobsCount = (int) ($company->jobs_count ?? 0);

        return [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'tagline' => $company->tagline,
            'location' => $company->location,
            'logo_url' => $company->logo_url,
            'jobs_count' => $jobsCount,
            'jobs_count_label' => __('companies.total_jobs', ['count' => $jobsCount]),
            'latest_jobs' => [],
        ];
    }

    /**
     * Serialize a paginator into the {data, meta} shape consumed by the
     * client's native InfiniteScroll component — no Eloquent models leak.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    private function serializedScroll(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => array_map(fn (Job $job) => $this->serializeJobCard($job), $paginator->items()),
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
