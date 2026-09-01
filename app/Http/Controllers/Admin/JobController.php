<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = JobStatus::tryFrom((string) $request->query('status', ''));
        $noApplications = $this->hasNoApplicationsFilter($request);
        $jobId = filter_var($request->query('job'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        $query = Job::query()
            ->with(['company', 'recruiter'])
            ->withCount('applications');

        if ($search !== '') {
            $query->where(function (Builder $jobs) use ($search): void {
                $jobs
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('company', function (Builder $company) use ($search): void {
                        $company->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('recruiter', function (Builder $recruiter) use ($search): void {
                        $recruiter
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        if ($noApplications) {
            $query->whereDoesntHave('applications');
        }

        if ($jobId !== false) {
            $query->whereKey($jobId);
        }

        $jobs = $query
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Jobs', [
            'jobs' => $jobs->getCollection()
                ->map(fn (Job $job): array => $this->serializeJob($job))
                ->values()
                ->all(),
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'next_page_url' => $jobs->nextPageUrl(),
                'prev_page_url' => $jobs->previousPageUrl(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status?->value ?? '',
                'filter' => $noApplications ? 'no_applications' : '',
                'no_applications' => $noApplications,
                'job' => $jobId === false ? null : $jobId,
            ],
            'statusOptions' => collect(JobStatus::cases())
                ->map(fn (JobStatus $jobStatus): array => [
                    'value' => $jobStatus->value,
                    'label' => __('admin.'.$jobStatus->value),
                ])
                ->values()
                ->all(),
            'labels' => $this->labels(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeJob(Job $job): array
    {
        $status = $job->status instanceof JobStatus
            ? $job->status->value
            : (string) $job->status;
        $applicationsCount = (int) ($job->applications_count ?? 0);

        return [
            'id' => $job->id,
            'title' => $job->title,
            'company' => $job->company ? [
                'id' => $job->company->id,
                'name' => $job->company->name,
                'slug' => $job->company->slug,
            ] : null,
            'recruiter' => $job->recruiter ? [
                'id' => $job->recruiter->id,
                'name' => $job->recruiter->name,
                'email' => $job->recruiter->email,
            ] : null,
            'status' => $status,
            'status_label' => __('admin.'.$status),
            'published_at' => $job->published_at?->toIso8601String(),
            'published_label' => $job->published_at?->translatedFormat('M j, Y'),
            'closes_at' => $job->closes_at?->toDateString(),
            'closes_label' => $job->closes_at?->translatedFormat('M j, Y'),
            'applications_count' => $applicationsCount,
            'created_at' => $job->created_at?->toIso8601String(),
            'created_label' => $job->created_at?->translatedFormat('M j, Y'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function labels(): array
    {
        $keys = [
            'job_management_title', 'job_management_subtitle', 'sidebar_overview', 'sidebar_management',
            'sidebar_users', 'sidebar_jobs', 'admin_area', 'search', 'search_button', 'jobs_search_placeholder',
            'all_statuses', 'no_applications_filter', 'clear', 'no_jobs_found', 'no_jobs_match', 'title',
            'company', 'recruiter', 'status', 'published', 'closing_date', 'applications', 'created',
            'draft', 'viewing_no_applications', 'viewing_job',
        ];

        return [
            ...collect($keys)
                ->mapWithKeys(fn (string $key): array => [$key => __('admin.'.$key)])
                ->all(),
            'show_more' => __('common.show_more'),
        ];
    }

    private function hasNoApplicationsFilter(Request $request): bool
    {
        $canonical = $request->query('no_applications');
        $canonicalEnabled = in_array((string) $canonical, ['1', 'true', 'on', 'yes'], true);

        return $canonicalEnabled || $request->query('filter') === 'no_applications';
    }
}
