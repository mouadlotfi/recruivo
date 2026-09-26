<?php

namespace App\Support;

use App\Models\Company;

/**
 * The single web shape for a company card (companies index + search grid).
 * `latest_jobs` is passed by the caller: the index page renders the loaded
 * take(3) jobs, search renders an empty list (jobs_count carries the count).
 */
final class CompanyCardSerializer
{
    /**
     * @param  array<int, array{id: int, title: string}>  $latestJobs
     * @return array<string, mixed>
     */
    public function serialize(Company $company, array $latestJobs = []): array
    {
        $jobsCount = (int) ($company->jobs_count ?? 0);

        return [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'tagline' => $company->tagline,
            'location' => $company->location,
            'logo_url' => $company->logo_url,
            'jobs_count' => $jobsCount,
            'jobs_count_label' => __('companies.total_jobs', ['count' => $jobsCount]),
            'latest_jobs' => $latestJobs,
        ];
    }
}
