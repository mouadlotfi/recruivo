<?php

namespace App\Http\Controllers\Recruiter;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Models\Job;
use App\Support\JobDescriptionFormatter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobController extends Controller
{
    /**
     * Page-string keys rendered by Recruiter/Jobs/Index.vue, resolved in the
     * current locale and passed as a flat `labels` prop (page strings travel
     * with the page, the shared shell translations stay lean).
     *
     * @var array<int, string>
     */
    private const INDEX_PAGE_LABEL_KEYS = [
        'my_job_listings', 'my_job_listings_subtitle', 'post_new_job', 'no_job_listings',
        'get_started_posting', 'post_first_job', 'expired', 'published', 'draft',
        'view_applications_title', 'edit_job_title_attr', 'delete_job_title',
        'delete_job_confirm', 'delete_job', 'unpublish', 'publish',
        'extend_closing_date_before_publishing', 'remote', 'hybrid', 'onsite',
    ];

    /**
     * Page-string keys for the shared create/edit form (Recruiter/Jobs/Create.vue
     * + Edit.vue via Components/Recruiter/JobForm.vue).
     *
     * @var array<int, string>
     */
    private const FORM_PAGE_LABEL_KEYS = [
        'create_job_title', 'create_job_subtitle', 'edit_job_title', 'edit_job_subtitle',
        'back_to_jobs', 'job_title', 'job_title_placeholder', 'location', 'location_placeholder',
        'job_description', 'description_placeholder', 'category', 'select_category',
        'engineering', 'design', 'product', 'marketing', 'sales', 'operations',
        'remote_type', 'select_remote_type', 'remote', 'hybrid', 'onsite', 'status',
        'draft', 'published', 'closing_date', 'closing_date_short', 'closing_date_help',
        'minimum_salary', 'maximum_salary', 'salary_placeholder_min', 'salary_placeholder_max',
        'create_job', 'update_job', 'cancel', 'expired', 'expired_job_notice',
        'extend_closing_date_before_publishing',
    ];

    /**
     * Page-string keys rendered by Recruiter/Jobs/Show.vue.
     *
     * @var array<int, string>
     */
    private const SHOW_PAGE_LABEL_KEYS = [
        'back_to_jobs', 'expired', 'expired_job_notice', 'published', 'draft',
        'view_applications_title', 'edit_job', 'location', 'category', 'remote_type',
        'remote', 'hybrid', 'onsite', 'unpublish', 'publish', 'closing_date_short',
    ];

    /**
     * Job categories offered in the create/edit form (values stored on the job,
     * labels resolved via recruiter.* translation keys).
     *
     * @var array<int, string>
     */
    private const CATEGORIES = ['Engineering', 'Design', 'Product', 'Marketing', 'Sales', 'Operations'];

    public function index(Request $request)
    {
        $jobs = $request->user()
            ->company
            ->jobs()
            ->withCount('applications')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Recruiter/Jobs/Index', [
            'jobs' => array_map(fn (Job $job) => $this->serializeJobSummary($job), $jobs->items()),
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'next_page_url' => $jobs->nextPageUrl(),
                'prev_page_url' => $jobs->previousPageUrl(),
            ],
            'labels' => $this->labelsFor(self::INDEX_PAGE_LABEL_KEYS) + [
                'show_more' => __('common.show_more'),
                'loading_more' => __('common.loading_more'),
                'load_more_failed' => __('common.load_more_failed'),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Recruiter/Jobs/Create', [
            'categories' => self::CATEGORIES,
            'labels' => $this->labelsFor(self::FORM_PAGE_LABEL_KEYS) + [
                'loading' => __('common.loading'),
            ],
        ]);
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

        return Inertia::render('Recruiter/Jobs/Show', [
            'job' => $this->serializeJobDetail($job),
            'labels' => $this->labelsFor(self::SHOW_PAGE_LABEL_KEYS) + [
                'job_description_label' => rtrim(__('recruiter.job_description'), ' *'),
                'job_details' => __('jobs.job_details'),
                'salary_range' => __('jobs.salary_range'),
            ],
        ]);
    }

    public function edit(string $locale, Job $job)
    {
        $this->authorize('update', $job);

        return Inertia::render('Recruiter/Jobs/Edit', [
            'job' => $this->serializeJobDetail($job),
            'categories' => self::CATEGORIES,
            'labels' => $this->labelsFor(self::FORM_PAGE_LABEL_KEYS) + [
                'loading' => __('common.loading'),
            ],
        ]);
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

        if ($job->isExpired()) {
            return back()->with('error', __('recruiter.extend_closing_date_before_publishing'));
        }

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

    /**
     * Resolve a flat `labels` map for a page from translation keys. Keys live
     * in the given lang file (defaults to recruiter.*).
     *
     * @param  array<int, string>  $keys
     * @return array<string, string>
     */
    private function labelsFor(array $keys, string $file = 'recruiter'): array
    {
        return collect($keys)->mapWithKeys(
            fn (string $key) => [$key => __("$file.$key")]
        )->all();
    }

    /**
     * Flat serialization of a job for the recruiter index card. User-visible
     * strings (posted time, applications count) are composed here in the
     * current locale; no Eloquent models leak into props.
     *
     * @return array<string, mixed>
     */
    private function serializeJobSummary(Job $job): array
    {
        $applicationsCount = (int) ($job->applications_count ?? 0);

        return [
            'id' => $job->id,
            'title' => $job->title,
            'status' => $job->status->value,
            'applications_count' => $applicationsCount,
            'published_at' => $job->published_at?->toIso8601String(),
            'closes_at' => $job->closes_at?->toIso8601String(),
            'is_expired' => $job->isExpired(),
            'created_at' => $job->created_at?->toIso8601String(),
            'posted_label' => $job->created_at
                ? __('recruiter.posted_time', ['time' => $job->created_at->diffForHumans()])
                : '',
            'published_label' => $job->published_at
                ? __('recruiter.published_on', ['date' => $job->published_at->translatedFormat('M j, Y')])
                : '',
            'closes_label' => $job->closes_at
                ? __('recruiter.closes_on', ['date' => $job->closes_at->translatedFormat('M j, Y')])
                : '',
            'applications_label' => trans_choice('recruiter.applications_count', $applicationsCount, ['count' => $applicationsCount]),
        ];
    }

    /**
     * Full serialization for the recruiter show/edit pages. `closes_at` is
     * Y-m-d (toDateString) so the edit form's <input type="date"> binds
     * directly. The description travels as both raw plain text (form) and
     * `description_html` — output of App\Support\JobDescriptionFormatter,
     * which escapes every user-controlled line, so the show page may render
     * it with v-html (the only v-html on that page).
     *
     * @return array<string, mixed>
     */
    private function serializeJobDetail(Job $job): array
    {
        return [
            ...$this->serializeJobSummary($job),
            'description' => $job->description,
            'description_html' => JobDescriptionFormatter::format((string) $job->description),
            'location' => $job->location,
            'category' => $job->category,
            'remote_type' => $job->remote_type,
            'salary_min' => $job->salary_min,
            'salary_max' => $job->salary_max,
            'closes_label' => $job->closes_at
                ? $job->closes_at->translatedFormat('M j, Y')
                : null,
        ];
    }
}
