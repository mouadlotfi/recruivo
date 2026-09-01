<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Ranked job/company search. The interface is the envelope: one call returns
 * both result sets plus the "did you mean" correction, so every surface (the
 * /search page, the API autocomplete) shares one ranking answer.
 *
 * Behavior contract:
 * - empty query + no filters        → empty envelope (search page empty state)
 * - empty query + filters           → DB-filtered, unscored, newest first
 * - text query                      → weighted PHP scoring (title > company >
 *                                     category > location > remote_type >
 *                                     description), with synonym expansion and
 *                                     small-typo tolerance
 * - zero results on a text query    → suggestedCorrection from the vocabulary
 *
 * Tuning (synonyms, weights, limits, typo distances) lives in config/search.php.
 */
class SmartSearchService
{
    public function normalize(?string $query): string
    {
        return Str::of((string) $query)->lower()->squish()->toString();
    }

    /**
     * Run a search and return everything a caller needs in one envelope.
     * Companies are only queried when there is a text query — a filter-only
     * search has no company results (matches the search page behavior).
     *
     * @param  string|null  $remoteType  Optional remote-type filter pushed into
     *                                   the SQL query so we don't materialize jobs
     *                                   merely to discard them in PHP.
     * @param  string|null  $location  Optional location substring filter,
     *                                 applied at the database level.
     */
    public function search(?string $query, ?string $remoteType = null, ?string $location = null): SearchEnvelope
    {
        $query = $this->normalize($query);
        $hasQuery = $query !== '';
        $hasFilters = $remoteType !== null || $location !== null;

        if (! $hasQuery && ! $hasFilters) {
            return new SearchEnvelope(collect(), collect());
        }

        $jobs = $this->jobs($query, $remoteType, $location);
        $companies = $hasQuery ? $this->companies($query, $location) : collect();

        $correction = $hasQuery && $jobs->isEmpty() && $companies->isEmpty()
            ? $this->suggestedCorrection($query)
            : null;

        return new SearchEnvelope($jobs, $companies, $correction);
    }

    /**
     * @param  string|null  $remoteType  Optional remote-type filter pushed into
     *                                   the SQL query so we don't materialize jobs
     *                                   merely to discard them in PHP.
     * @param  string|null  $location  Optional location substring filter,
     *                                 applied at the database level.
     * @return Collection<int, Job>
     */
    public function jobs(string $query, ?string $remoteType = null, ?string $location = null): Collection
    {
        $base = fn () => Job::published()
            ->with('company')
            ->withSavedStateFor(auth()->user())
            ->when($remoteType, fn ($builder) => $builder->where('remote_type', $remoteType))
            ->when($location, fn ($builder) => $builder->where('location', 'like', '%'.$location.'%'))
            ->latest('published_at')
            ->limit($this->limit('jobs'));

        if ($this->normalize($query) === '') {
            return $base()->get();
        }

        return $base()->get()
            ->map(fn (Job $job) => ['model' => $job, 'score' => $this->jobScore($job, $query)])
            ->filter(fn (array $result) => $result['score'] > 0)
            ->sortByDesc('score')
            ->pluck('model')
            ->values();
    }

    /**
     * @param  string|null  $location  Optional location substring filter,
     *                                 applied at the database level.
     * @return Collection<int, Company>
     */
    public function companies(string $query, ?string $location = null): Collection
    {
        return Company::query()
            ->withCount('jobs')
            ->when($location, fn ($builder) => $builder->where('location', 'like', '%'.$location.'%'))
            ->latest()
            ->limit($this->limit('companies'))
            ->get()
            ->map(fn (Company $company) => ['model' => $company, 'score' => $this->companyScore($company, $query)])
            ->filter(fn (array $result) => $result['score'] > 0)
            ->sortByDesc('score')
            ->pluck('model')
            ->values();
    }

    /**
     * "Did you mean" for a query that returned nothing: find the vocabulary
     * word within levenshtein distance of the query.
     */
    public function suggestedCorrection(string $query): ?string
    {
        $query = $this->normalize($query);
        if (mb_strlen($query) < $this->typo('min_length')) {
            return null;
        }

        $vocabulary = Job::published()->limit($this->suggestion('vocabulary_jobs'))->pluck('title')
            ->merge(Company::limit($this->suggestion('vocabulary_companies'))->pluck('name'))
            ->flatMap(fn ($value) => preg_split('/[^\pL\pN]+/u', $this->normalize($value), -1, PREG_SPLIT_NO_EMPTY))
            ->unique();

        $best = $vocabulary
            ->map(fn ($word) => ['word' => $word, 'distance' => levenshtein($query, $word)])
            ->filter(fn ($match) => $match['distance'] <= $this->allowedDistance($query))
            ->sortBy('distance')
            ->first();

        return $best['word'] ?? null;
    }

    private function jobScore(Job $job, string $query): int
    {
        $weights = config('search.weights.jobs');

        return $this->score($query, [
            [$job->title, $weights['title']],
            [$job->company?->name, $weights['company']],
            [$job->category, $weights['category']],
            [$job->location, $weights['location']],
            [$job->remote_type, $weights['remote_type']],
            [$job->description, $weights['description']],
        ]);
    }

    private function companyScore(Company $company, string $query): int
    {
        $weights = config('search.weights.companies');

        return $this->score($query, [
            [$company->name, $weights['name']],
            [$company->tagline, $weights['tagline']],
            [$company->location, $weights['location']],
            [$company->mission, $weights['mission']],
            [$company->culture, $weights['culture']],
        ]);
    }

    /**
     * @param  array<int, array{0: mixed, 1: int}>  $fields
     */
    private function score(string $query, array $fields): int
    {
        $query = $this->normalize($query);
        if ($query === '') {
            return 0;
        }

        $terms = collect(preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY));
        $score = 0;
        $matchedTerms = 0;

        foreach ($fields as [$rawValue, $weight]) {
            $value = $this->normalize(strip_tags((string) $rawValue));
            if ($value === '') {
                continue;
            }

            if ($value === $query) {
                $score += $weight * 4;
            } elseif (str_starts_with($value, $query)) {
                $score += $weight * 3;
            } elseif (str_contains($value, $query)) {
                $score += $weight * 2;
            }

            $words = preg_split('/[^\pL\pN]+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($terms as $term) {
                $alternatives = collect([$term])->merge(config('search.synonyms.'.$term, []));
                $termMatched = $alternatives->contains(fn ($candidate) => str_contains($value, $candidate) ||
                    collect($words)->contains(fn ($word) => $this->isCloseMatch($candidate, $word))
                );

                if ($termMatched) {
                    $score += $weight;
                    $matchedTerms++;
                }
            }
        }

        if ($terms->count() > 1 && $matchedTerms >= $terms->count()) {
            $score += 80;
        }

        return $score;
    }

    private function isCloseMatch(string $query, string $word): bool
    {
        if (mb_strlen($query) < $this->typo('min_length')
            || abs(mb_strlen($query) - mb_strlen($word)) > $this->typo('length_tolerance')) {
            return false;
        }

        return levenshtein($query, $word) <= $this->allowedDistance($query);
    }

    private function allowedDistance(string $query): int
    {
        return mb_strlen($query) >= 8
            ? $this->typo('long_query_distance')
            : $this->typo('short_query_distance');
    }

    private function limit(string $key): int
    {
        return (int) config("search.limits.$key");
    }

    private function typo(string $key): int
    {
        return (int) config("search.typo.$key");
    }

    private function suggestion(string $key): int
    {
        return (int) config("search.suggestions.$key");
    }
}
