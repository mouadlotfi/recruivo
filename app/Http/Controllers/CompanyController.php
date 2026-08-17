<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use Inertia\Inertia;

class CompanyController extends Controller
{
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
        'back_to_companies', 'founded', 'website', 'linkedin', 'our_mission',
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
                ->map(fn (Company $company) => $this->serializeCompanyCard($company))
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
     * Flat serialization of a company for the index grid — no Eloquent
     * models leak into props.
     *
     * @return array<string, mixed>
     */
    private function serializeCompanyCard(Company $company): array
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
            'latest_jobs' => $company->jobs
                ->map(fn (Job $job) => ['id' => $job->id, 'title' => $job->title])
                ->values()
                ->all(),
        ];
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
                ->map(fn (Job $job) => $this->serializeJobCard($job, $company))
                ->values()
                ->all(),
        ];
    }

    /**
     * Flat serialization of a job in the JobSummary shape consumed by
     * Components/Jobs/JobCard.vue; the company block comes from the loaded
     * parent so no extra relation query runs.
     *
     * @return array<string, mixed>
     */
    private function serializeJobCard(Job $job, Company $company): array
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
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'logo_url' => $company->logo_url,
            ],
        ];
    }
}
