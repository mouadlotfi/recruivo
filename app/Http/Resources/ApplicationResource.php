<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status instanceof \App\Enums\ApplicationStatus ? $this->status->value : $this->status,
            'original_status' => $this->original_status,
            'resume_path' => $this->resume_path,
            'cover_letter' => $this->cover_letter,
            'notes' => $this->notes,
            'status_changed' => $this->status_changed,
            'notes_added' => $this->notes_added,
            'status_changed_at' => $this->status_changed_at?->toIso8601String(),
            'notes_added_at' => $this->notes_added_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'interview_mode' => $this->interview_mode,
            'interview_at' => $this->interview_at?->toIso8601String(),
            'interview_location' => $this->interview_location,
            'interview_url' => $this->interview_url,
            'interview_instructions' => $this->interview_instructions,
            'candidate' => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'email' => $this->candidate->email,
                'phone' => $this->candidate->phone,
            ],
            'job' => new JobResource($this->whenLoaded('job') ?? $this->job),
            'status_history' => $this->whenLoaded('statusEvents', fn () =>
                $this->statusEvents->map(fn ($event) => [
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'changed_at' => $event->created_at?->toIso8601String(),
                    'changed_by' => $event->changedBy?->name,
                ])
            ),
        ];
    }
}
