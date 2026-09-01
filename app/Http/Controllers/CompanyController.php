<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use App\Support\CompanyCardSerializer;
use App\Support\JobCardSerializer;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function __construct(
        private readonly JobCardSerializer $jobCards,
        private readonly CompanyCardSerializer $companyCards,
    ) {}

    /**
     * Page-string keys for the companies index (companies.*), resolved in the
     * current locale and passed as a flat `labels` prop — page strings
     * travel with the page, the shared shell translations stay lean.
     *
     * @var array<int, string>
     */
    private const INDEX_PAGE_LABEL_KEYS = [
        'browse_companies_title', 'browse_companies_subtitle', 'no_companies_found_index',
        'check_back_for_companies', 'latest_jobs',
    ];

    /**
     * Page-string keys for the company show page (companies.*).
     *
     * @var array<int, string>
     */
    private const SHOW_PAGE_LABEL_KEYS = [
        'back_to_companies', 'back_to_admin_users', 'founded', 'website', 'linkedin', 'our_mission',
        'company_culture', 'no_open_positions',
    ];

    /**
     * Job-card keys (jobs.*) needed by Components/Jobs/JobCard.vue on the
     * company show page's open-positions grid.
     *
     * @var array<int, string>
     */
    private const SHOW_JOB_LABEL_KEYS = [
        'remote', 'hybrid', 'onsite', 'closing_soon', 'save_job', 'remove_saved_job',
        'demo_cannot_save_jobs',
    ];

    public function index()
    {
        if (request()->user()?->hasRole('Admin')) {
            return redirect(localized_route('admin.dashboard'));
        }
        $companies = Company::withCount('jobs')
            ->with(['jobs' => function ($query) {
                $query->published()
                    ->select('id', 'company_id', 'title', 'published_at')
                    ->latest('published_at')
                    ->take(3);
            }])
            ->latest()
            ->paginate(12);

        return Inertia::render('Companies/Index', [
            'companies' => $companies->getCollection()
                ->map(fn (Company $company) => $this->companyCards->serialize(
                    $company,
                    $company->jobs
                        ->map(fn (Job $job) => ['id' => $job->id, 'title' => $job->title])
                        ->values()
                        ->all(),
                ))
                ->all(),
            'pagination' => [
                'total' => $companies->total(),
                'per_page' => $companies->perPage(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'next_page_url' => $companies->nextPageUrl(),
                'prev_page_url' => $companies->previousPageUrl(),
            ],
            'labels' => [
                ...collect(self::INDEX_PAGE_LABEL_KEYS)->mapWithKeys(
                    fn (string $key) => [$key => __("companies.$key")]
                )->all(),
                'show_more' => __('common.show_more'),
                'loading_more' => __('common.loading_more'),
                'load_more_failed' => __('common.load_more_failed'),
            ],
        ]);
    }

    public function show(string $locale, string $slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        $user = auth()->user();

        $company->load(['jobs' => function ($query) use ($user) {
            $query->published()
                ->latest('published_at');

            if ($user?->hasRole('Candidate')) {
                $query->withExists([
                    'applications as has_applied' => fn ($q) => $q->where('candidate_id', $user->id),
                    'savedByCandidates as is_saved' => fn ($q) => $q->whereKey($user->id),
                ]);
            }
        }]);

        return Inertia::render('Companies/Show', [
            'company' => $this->serializeCompanyDetail($company),
            'labels' => [
                ...collect(self::SHOW_PAGE_LABEL_KEYS)->mapWithKeys(
                    fn (string $key) => [$key => __("companies.$key")]
                )->all(),
                ...collect(self::SHOW_JOB_LABEL_KEYS)->mapWithKeys(
                    fn (string $key) => [$key => __("jobs.$key")]
                )->all(),
                'applied' => __('common.applied'),
                'positions_heading' => $company->jobs->isNotEmpty()
                    ? __('companies.open_positions_count', ['count' => $company->jobs->count()])
                    : __('companies.open_positions'),
                'no_positions_message' => __('companies.check_back_later', ['company' => $company->name]),
            ],
        ]);
    }

    /**
     * Full serialization for the company show page; jobs use the same
     * JobSummary shape Components/Jobs/JobCard.vue expects (company included
     * so the card renders).
     *
     * @return array<string, mixed>
     */
    private function serializeCompanyDetail(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'tagline' => $company->tagline,
            'location' => $company->location,
            'size' => $company->size,
            'logo_url' => $company->logo_url,
            'mission' => $company->mission,
            'culture' => $company->culture,
            'founded_year' => $company->founded_year,
            'website_url' => $company->website_url,
            'linkedin_url' => $company->linkedin_url,
            'jobs' => $company->jobs
                ->map(fn (Job $job) => $this->jobCards->serialize($job, $company))
                ->values()
                ->all(),
        ];
    }
}
