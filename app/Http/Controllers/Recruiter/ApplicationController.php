<?php

namespace App\Http\Controllers\Recruiter;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Models\Application;
use App\Models\Job;
use App\Notifications\ApplicationStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    /**
     * Page-string keys rendered by Recruiter/Applications/Index.vue, resolved
     * in the current locale and passed as a flat `labels` prop (keeps the
     * shell's shared translations lean — page strings travel with the page).
     *
     * @var array<int, string>
     */
    private const PAGE_LABEL_KEYS = [
        'all_statuses', 'filter_applications', 'pending', 'shortlisted', 'interview', 'accepted', 'rejected', 'withdrawn',
        'review_application', 'select_status', 'note_templates', 'add_notes_placeholder',
        'interview_details', 'interview_mode', 'interview_onsite', 'interview_online',
        'interview_at', 'interview_location', 'interview_url', 'interview_instructions',
        'interview_details_hint', 'your_notes', 'cover_letter', 'status_timeline',
        'withdrawn_by_candidate', 'view_resume', 'phone', 'not_provided', 'applied_time',
        'application_updated', 'resume_not_found', 'manage_templates', 'back_to_jobs',
        'no_applications_received', 'no_applications_with_status_message',
        'applications_appear_message', 'expand_notes', 'expand_interview_instructions',
    ];

    public function index(Request $request, string $locale, Job $job)
    {
        $this->authorize('view', $job);

        $status = $request->query('status', 'all');
        $allowedStatuses = array_map(fn (ApplicationStatus $status) => $status->value, ApplicationStatus::cases());

        if ($status !== 'all' && ! in_array($status, $allowedStatuses, true)) {
            return redirect(localized_route('recruiter.jobs.applications', $job));
        }

        $statusCounts = $job->applications()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $applications = $job->applications()
            ->with(['candidate.candidateProfile', 'statusEvents.changedBy:id,name'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $noteTemplates = $request->user()->noteTemplates()->orderBy('name')->get();

        return Inertia::render('Recruiter/Applications/Index', [
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
            ],
            'status' => $status,
            'statusCounts' => collect([['key' => 'all', 'count' => $statusCounts->sum()]])
                ->merge(
                    collect(ApplicationStatus::cases())->map(
                        fn (ApplicationStatus $case) => [
                            'key' => $case->value,
                            'count' => $statusCounts->get($case->value, 0),
                        ]
                    )
                )
                ->map(fn (array $count) => $count + ['label_key' => $count['key']])
                ->values()
                ->all(),
            'applications' => $applications->through(
                fn (Application $application) => $this->serializeApplication($application)
            )->items(),
            'noteTemplates' => $noteTemplates->map(fn ($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'body' => $template->body,
            ])->values()->all(),
            'pagination' => [
                'total' => $applications->total(),
                'per_page' => $applications->perPage(),
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'next_page_url' => $applications->nextPageUrl(),
                'prev_page_url' => $applications->previousPageUrl(),
            ],
            'labels' => $this->pageLabels($status, $job, $applications->total()),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function pageLabels(string $status, Job $job, int $total): array
    {
        $labels = collect(self::PAGE_LABEL_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => __("recruiter.$key")])
            ->all();

        return $labels + [
            'applications_for' => __('recruiter.applications_for', ['job' => $job->title]),
            'applications_received' => trans_choice('recruiter.applications_received', $total, ['count' => $total]),
            'filtered_applications_received' => trans_choice('recruiter.filtered_applications_received', $total, ['count' => $total]),
            'no_applications_with_status' => __('recruiter.no_applications_with_status', [
                'status' => strtolower(__('recruiter.'.$status)),
            ]),
            'status_pending' => __('applications.status_pending'),
            'status_shortlisted' => __('applications.status_shortlisted'),
            'status_interview' => __('applications.status_interview'),
            'status_accepted' => __('applications.status_accepted'),
            'status_rejected' => __('applications.status_rejected'),
            'status_withdrawn' => __('applications.status_withdrawn'),
            'update' => __('common.update'),
            'expand' => __('common.expand'),
            'cancel' => __('common.cancel'),
            'done' => __('common.done'),
            'close' => __('common.close'),
            'show_more' => __('common.show_more'),
            'loading_more' => __('common.loading_more'),
            'load_more_failed' => __('common.load_more_failed'),
        ];
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
        $candidate = $application->candidate;
        $profile = $candidate->candidateProfile;
        $statusValue = $application->status->value;

        $hasResume = (bool) ($application->resume_path || $profile?->resume_path);

        $interview = null;
        if ($statusValue === ApplicationStatus::Interview->value && $application->interview_at) {
            $interview = [
                'at' => $application->interview_at->toIso8601String(),
                'mode' => $application->interview_mode,
                'location' => $application->interview_location,
                'url' => $application->interview_url,
                'instructions' => $application->interview_instructions,
                'formatted_at' => $application->interview_at->translatedFormat('l, F j, Y \\a\\t g:i A'),
            ];
        }

        return [
            'id' => $application->id,
            'status' => $statusValue,
            'status_label' => __("recruiter.$statusValue"),
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'has_resume' => $hasResume,
                'resume_url' => $hasResume ? localized_route('recruiter.applications.resume', $application) : null,
                'profile' => $profile ? [
                    'headline' => $profile->headline,
                    'summary' => $candidate->profile_summary,
                    'skills' => $profile->skills,
                    'location' => $candidate->location,
                ] : null,
            ],
            'cover_letter' => $application->cover_letter ? trim($application->cover_letter) : null,
            'notes' => $application->notes,
            'notes_added' => (bool) $application->notes_added,
            'status_changed_at' => $application->status_changed_at?->toIso8601String(),
            'created_at' => $application->created_at?->toIso8601String(),
            'applied_label' => __('recruiter.applied_time', ['time' => $application->created_at->diffForHumans()]),
            'timeline' => $application->statusEvents->map(fn ($event) => [
                'to_status' => $event->to_status,
                'from_status' => $event->from_status,
                'created_at' => $event->created_at->toIso8601String(),
                'formatted_at' => $event->created_at->translatedFormat('M j, Y H:i'),
                'label' => __("applications.status_{$event->to_status}"),
                'changed_by_name' => $event->changedBy?->name,
            ])->values()->all(),
            'interview' => $interview,
            'can_review' => ! in_array($statusValue, ['accepted', 'rejected', 'withdrawn'], true),
            'is_withdrawn' => $statusValue === ApplicationStatus::Withdrawn->value,
        ];
    }

    public function update(UpdateApplicationStatusRequest $request, string $locale, Application $application)
    {
        $this->authorize('update', $application);

        $previousStatus = $application->status;
        $application->applyStatusUpdate($request->validated());

        // Send notification if status changed
        if ($previousStatus != $application->status) {
            $application->candidate->notify(
                new ApplicationStatusUpdatedNotification($application)
            );
        }

        return back()->with('success', __('recruiter.application_updated'));
    }

    public function downloadResume(string $locale, Application $application)
    {
        $this->authorize('view', $application);

        $resumePath = $application->resume_path ?: $application->candidate->candidateProfile?->resume_path;

        if (! $resumePath || ! Storage::disk('private')->exists($resumePath)) {
            return back()->with('error', __('recruiter.resume_not_found'));
        }

        return response()->file(Storage::disk('private')->path($resumePath), [
            'Content-Disposition' => 'inline; filename="'.basename($resumePath).'"',
        ]);
    }
}
