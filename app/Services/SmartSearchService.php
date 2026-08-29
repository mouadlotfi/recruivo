<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SmartSearchService
{
    private const SYNONYMS = [
        'dev' => ['developer', 'engineering'],
        'developer' => ['dev', 'engineer'],
        'engineer' => ['engineering', 'developer'],
        'remote' => ['distributed', 'telecommute'],
        'onsite' => ['on-site', 'office'],
        'hr' => ['human resources', 'recruiting'],
        'ui' => ['user interface', 'design'],
        'ux' => ['user experience', 'design'],
    ];

    public function normalize(?string $query): string
    {
        return Str::of((string) $query)->lower()->squish()->toString();
    }

    /**
     * @param  string|null  $remoteType  Optional remote-type filter pushed into
     *                                   the SQL query so we don't materialize jobs
     *                                   merely to discard them in PHP.
     * @param  string|null  $location  Optional location substring filter,
     *                                 applied at the database level.
     */
    public function jobs(string $query, ?string $remoteType = null, ?string $location = null): Collection
    {
        return Job::published()
            ->with('company')
            ->withSavedStateFor(auth()->user())
            ->when($remoteType, fn ($builder) => $builder->where('remote_type', $remoteType))
            ->when($location, fn ($builder) => $builder->where('location', 'like', '%'.$location.'%'))
            ->latest('published_at')
            ->limit(300)
            ->get()
            ->map(fn (Job $job) => ['model' => $job, 'score' => $this->jobScore($job, $query)])
            ->filter(fn (array $result) => $result['score'] > 0)
            ->sortByDesc('score')
            ->pluck('model')
            ->values();
    }

    /**
     * @param  string|null  $location  Optional location substring filter,
     *                                 applied at the database level.
     */
    public function companies(string $query, ?string $location = null): Collection
    {
        return Company::query()
            ->withCount('jobs')
            ->when($location, fn ($builder) => $builder->where('location', 'like', '%'.$location.'%'))
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Company $company) => ['model' => $company, 'score' => $this->companyScore($company, $query)])
            ->filter(fn (array $result) => $result['score'] > 0)
            ->sortByDesc('score')
            ->pluck('model')
            ->values();
    }

    public function suggestedCorrection(string $query, Collection $jobs, Collection $companies): ?string
    {
        if ($jobs->isNotEmpty() || $companies->isNotEmpty() || mb_strlen($query) < 4) {
            return null;
        }

        $vocabulary = Job::published()->limit(200)->pluck('title')
            ->merge(Company::limit(100)->pluck('name'))
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
        return $this->score($query, [
            [$job->title, 120],
            [$job->company?->name, 90],
            [$job->category, 65],
            [$job->location, 55],
            [$job->remote_type, 45],
            [$job->description, 15],
        ]);
    }

    private function companyScore(Company $company, string $query): int
    {
        return $this->score($query, [
            [$company->name, 120],
            [$company->tagline, 70],
            [$company->location, 55],
            [$company->mission, 20],
            [$company->culture, 15],
        ]);
    }

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
                $alternatives = collect([$term])->merge(self::SYNONYMS[$term] ?? []);
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
        if (mb_strlen($query) < 4 || abs(mb_strlen($query) - mb_strlen($word)) > 2) {
            return false;
        }

        return levenshtein($query, $word) <= $this->allowedDistance($query);
    }

    private function allowedDistance(string $query): int
    {
        return mb_strlen($query) >= 8 ? 2 : 1;
    }
}
