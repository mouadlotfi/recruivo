<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Collection;

/**
 * Result of a search: the ranked job/company collections plus the optional
 * "did you mean" correction. Callers slice pages off the collections and read
 * their totals directly.
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
}
