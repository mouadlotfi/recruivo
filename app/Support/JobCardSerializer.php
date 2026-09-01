<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Job;

/**
 * The single web shape for a job card. Used by every page that renders a
 * job card (jobs index, search, home, company show) so prop shapes can't
 * drift between controllers. No Eloquent models leak into props; the
 * `is_saved`/`has_applied` flags come from withExists subqueries (false for
 * guests and non-candidates).
 */
final class JobCardSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(Job $job, ?Company $company = null): array
    {
        // Caller may pass the already-loaded parent (company show page) to
        // avoid touching the relation when it would re-query.
        $company ??= $job->company;

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
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'logo_url' => $company->logo_url,
            ] : null,
        ];
    }
}
