<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'location' => $this->location,
            'size' => $this->size,
            'logo_url' => $this->logo_url,
            'jobs_count' => $this->jobs_count ?? 0,
            'preview_jobs' => $this->whenLoaded('jobs', function () {
                return $this->jobs->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'published_at' => optional($job->published_at)->toIso8601String(),
                    ];
                })->values();
            }, []),
        ];
    }
}
