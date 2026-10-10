<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Models\Application;

/**
 * The "what happened to this application" block: the scheduled interview and the
 * status timeline. Both the candidate's own applications page and the recruiter's
 * review page render it, and their payloads have to stay identical.
 */
final class ApplicationTimelineSerializer
{
    /**
     * @return array<string, mixed>|null
     */
    public function interview(Application $application): ?array
    {
        // Nothing to show unless the application is actually at the interview
        // stage with a time set.
        if ($application->status !== ApplicationStatus::Interview || $application->interview_at === null) {
            return null;
        }

        return [
            'at' => $application->interview_at->toIso8601String(),
            'mode' => $application->interview_mode,
            'location' => $application->interview_location,
            'url' => $application->interview_url,
            'instructions' => $application->interview_instructions,
            'formatted_at' => $application->interview_at->translatedFormat('l, F j, Y \a\t g:i A'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function events(Application $application): array
    {
        return $application->statusEvents->map(fn ($event) => [
            'to_status' => $event->to_status,
            'from_status' => $event->from_status,
            'created_at' => $event->created_at->toIso8601String(),
            'formatted_at' => $event->created_at->translatedFormat('M j, Y H:i'),
            'label' => __("applications.status_{$event->to_status}"),
            'changed_by_name' => $event->changedBy?->name,
        ])->values()->all();
    }
}
