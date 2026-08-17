<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\DemoAccountGuard;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    /**
     * Page-string keys rendered by Candidate/Applications.vue, resolved in the
     * current locale and passed as a flat `labels` prop (keeps the shell's
     * shared translations lean — page strings travel with the page).
     *
     * @var array<int, string>
     */
    private const PAGE_LABEL_KEYS = [
        'my_applications', 'subtitle', 'browse_jobs', 'all', 'pending', 'pending_review',
        'shortlisted', 'interview', 'accepted', 'rejected', 'withdrawn',
        'no_applications_yet', 'no_applications_for_status', 'start_applying',
        'browse_available_jobs', 'your_cover_letter', 'recruiter_notes', 'status_timeline',
        'interview_scheduled', 'interview_when', 'interview_where', 'interview_link',
        'interview_online', 'interview_onsite', 'salary', 'view_job',
        'withdraw_application', 'withdraw_confirm',
    ];

    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $validStatuses = array_map(fn (ApplicationStatus $case) => $case->value, ApplicationStatus::cases());

        if ($status !== 'all' && ! in_array($status, $validStatuses, true)) {
            return redirect()->route('candidate.applications', ['locale' => app()->getLocale()]);
        }

        $baseQuery = $request->user()->applications();
        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            ...collect(ApplicationStatus::cases())->mapWithKeys(
                fn (ApplicationStatus $case) => [$case->value => (clone $baseQuery)->where('status', $case->value)->count()]
            )->all(),
        ];

        $applications = $baseQuery
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with(['job.company', 'statusEvents.changedBy:id,name'])
            ->latest()
            ->oldest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Application $application) => $this->serializeApplication($application));

        return Inertia::render('Candidate/Applications', [
            'applications' => $applications->items(),
            'status' => $status,
            'statusCounts' => collect($statusCounts)
                ->map(fn (int $count, string $key) => ['key' => $key, 'label_key' => $key, 'count' => $count])
                ->values()
                ->all(),
            'pagination' => [
                'total' => $applications->total(),
                'per_page' => $applications->perPage(),
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'next_page_url' => $applications->nextPageUrl(),
                'prev_page_url' => $applications->previousPageUrl(),
            ],
            'labels' => [
                ...collect(self::PAGE_LABEL_KEYS)->mapWithKeys(
                    fn (string $key) => [$key => __("applications.$key")]
                )->all(),
                'show_more' => __('common.show_more'),
                'loading_more' => __('common.loading_more'),
                'load_more_failed' => __('common.load_more_failed'),
            ],
        ]);
    }

    public function withdraw(Request $request, string $locale, Application $application, DemoAccountGuard $demoAccountGuard)
    {
        $this->authorize('withdraw', $application);
        $demoAccountGuard->ensureCandidateActionsAreMutable($request->user());

        $application->applyStatusUpdate(['status' => ApplicationStatus::Withdrawn->value]);

        return back()->with('success', __('applications.withdrawn_success'));
    }

    /**
     * Explicit serialization for the Inertia page — no Eloquent models leak
     * into props. User-visible strings (status label, applied time, dates)
     * are composed here in the current locale.
     *
     * @return array<string, mixed>
     */
    private function serializeApplication(Application $application): array
    {
        $job = $application->job;
        $company = $job->company;

        $interview = null;
        if ($application->status->value === ApplicationStatus::Interview->value && $application->interview_at) {
            $interview = [
                'at' => $application->interview_at->toIso8601String(),
                'mode' => $application->interview_mode,
                'location' => $application->interview_location,
                'url' => $application->interview_url,
                'instructions' => $application->interview_instructions,
                'formatted_at' => $application->interview_at->translatedFormat('l, F j, Y \a\t g:i A'),
            ];
        }

        return [
            'id' => $application->id,
            'status' => $application->status->value,
            'status_label' => __(
                'applications.'.($application->status->value === ApplicationStatus::Pending->value
                    ? 'pending_review'
                    : $application->status->value)
            ),
            'cover_letter' => $application->cover_letter ? trim($application->cover_letter) : null,
            'notes' => $application->notes,
            'notes_added' => (bool) $application->notes_added,
            'created_at' => $application->created_at?->toIso8601String(),
            'applied_label' => __('applications.applied', ['time' => $application->created_at->diffForHumans()]),
            'interview' => $interview,
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $company ? [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'logo_url' => $company->logo_url,
                ] : null,
                'location' => $job->location,
                'remote_type' => $job->remote_type,
                'category' => $job->category,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'closes_at' => $job->closes_at?->toIso8601String(),
                'is_closing_soon' => $job->isClosingSoon(),
            ],
            'timeline' => $application->statusEvents->map(fn ($event) => [
                'to_status' => $event->to_status,
                'from_status' => $event->from_status,
                'created_at' => $event->created_at->toIso8601String(),
                'formatted_at' => $event->created_at->translatedFormat('M j, Y H:i'),
                'label' => __("applications.status_{$event->to_status}"),
                'changed_by_name' => $event->changedBy?->name,
            ])->values()->all(),
        ];
    }
}
