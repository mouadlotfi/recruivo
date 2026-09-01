<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Collection;

/**
 * Result of a search: the ranked job/company collections plus the optional
 * "did you mean" correction. Counts are derived so callers (web page, API
 * autocomplete) never re-derive totals from paginated slices.
 */
final class SearchEnvelope
{
    /**
     * @param  Collection<int, Job>  $jobs
     * @param  Collection<int, Company>  $companies
     */
    public function __construct(
        public readonly Collection $jobs,
        public readonly Collection $companies,
        public readonly ?string $suggestedCorrection = null,
    ) {}

    public function jobCount(): int
    {
        return $this->jobs->count();
    }

    public function companyCount(): int
    {
        return $this->companies->count();
    }

    public function totalCount(): int
    {
        return $this->jobCount() + $this->companyCount();
    }
}
