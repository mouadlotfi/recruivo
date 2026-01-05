<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'location' => $this->location,
            'website_url' => $this->website_url,
            'linkedin_url' => $this->linkedin_url,
            'email' => $this->email,
            'size' => $this->size,
            'founded_year' => $this->founded_year,
            'mission' => $this->mission,
            'culture' => $this->culture,
            'logo_url' => $this->logo_url,
            'jobs' => $this->whenLoaded('jobs', function () {
                return $this->jobs->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'location' => $job->location,
                        'category' => $job->category,
                        'remote_type' => $job->remote_type,
                        'salary_min' => $job->salary_min,
                        'salary_max' => $job->salary_max,
                        'published_at' => optional($job->published_at)->toIso8601String(),
                        'applications_count' => $job->applications_count ?? 0,
                    ];
                })->values();
            }, []),
        ];
    }
}
