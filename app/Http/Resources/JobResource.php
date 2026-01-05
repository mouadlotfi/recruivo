<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'category' => $this->category,
            'remote_type' => $this->remote_type,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'status' => $this->status instanceof \App\Enums\JobStatus ? $this->status->value : $this->status,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'excerpt' => Str::limit(strip_tags($this->description), 180),
            'applications_count' => $this->whenCounted('applications'),
            'company' => CompanySummaryResource::make($this->whenLoaded('company')),
        ];
    }
}
