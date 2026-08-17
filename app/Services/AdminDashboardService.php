<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AdminDashboardService
{
    public const DEFAULT_RANGE = 30;

    /**
     * @var array<int, string>
     */
    public const RANGE_OPTIONS = [
        7 => 'last_7_days',
        30 => 'last_30_days',
        90 => 'last_90_days',
        365 => 'last_year',
    ];

    /**
     * @var array<int, string>
     */
    private const ACTIVITY_LIMIT_PER_SOURCE = 8;

    public function normalizeRange(mixed $range): int
    {
        $range = filter_var($range, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return array_key_exists($range, self::RANGE_OPTIONS)
            ? $range
            : self::DEFAULT_RANGE;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(int $range): array
    {
        $period = $this->period($range);
        $aggregation = $range === 365 ? 'month' : 'day';
        $buckets = $this->buckets($period['start'], $period['end'], $aggregation);

        $registeredUsers = $this->registeredUsersQuery();
        $recruiters = $this->recruitersQuery();
        $publishedJobs = Job::query()->whereNotNull('published_at');
        $applications = Application::query();

        $applicationCount = $this->countBetween($applications, 'created_at', $period['start'], $period['end']);
        $previousApplicationCount = $this->countBetween($applications, 'created_at', $period['previous_start'], $period['previous_end']);
        $registeredUsersInPeriod = $this->countBetween($registeredUsers, 'created_at', $period['start'], $period['end']);
        $previousRegisteredUsers = $this->countBetween($this->registeredUsersQuery(), 'created_at', $period['previous_start'], $period['previous_end']);
        $jobsPostedInPeriod = $this->countBetween($publishedJobs, 'published_at', $period['start'], $period['end']);
        $previousJobsPosted = $this->countBetween(Job::query()->whereNotNull('published_at'), 'published_at', $period['previous_start'], $period['previous_end']);
        $recruitersInPeriod = $this->countBetween($recruiters, 'created_at', $period['start'], $period['end']);
        $previousRecruiters = $this->countBetween($this->recruitersQuery(), 'created_at', $period['previous_start'], $period['previous_end']);

        $liveJobCount = Job::published()->count();
        $registeredUserCount = $registeredUsers->count();
        $recruiterCount = $recruiters->count();

        $growthSeries = [
            'users' => $this->groupedSeries($this->registeredUsersQuery(), 'created_at', $period, $buckets, $aggregation),
            'jobs' => $this->groupedSeries(Job::query()->whereNotNull('published_at'), 'published_at', $period, $buckets, $aggregation),
            'applications' => $this->groupedSeries(Application::query(), 'created_at', $period, $buckets, $aggregation),
            'recruiters' => $this->groupedSeries($this->recruitersQuery(), 'created_at', $period, $buckets, $aggregation),
        ];

        $jobsWithoutApplications = $this->jobsWithoutApplicationsCount();
        $activitySeries = [
            'applications' => $growthSeries['applications'],
            'jobs' => $growthSeries['jobs'],
            'users' => $growthSeries['users'],
        ];

        return [
            'range' => $range,
            'period' => $period,
            'metrics' => [
                'users' => [
                    'value' => $registeredUserCount,
                    'period_count' => $registeredUsersInPeriod,
                    'comparison' => $this->comparison($registeredUsersInPeriod, $previousRegisteredUsers),
                ],
                'live_jobs' => [
                    'value' => $liveJobCount,
                    'period_count' => $jobsPostedInPeriod,
                    'comparison' => $this->comparison($jobsPostedInPeriod, $previousJobsPosted),
                ],
                'applications' => [
                    'value' => $applicationCount,
                    'period_count' => $applicationCount,
                    'comparison' => $this->comparison($applicationCount, $previousApplicationCount),
                ],
                'recruiters' => [
                    'value' => $recruiterCount,
                    'period_count' => $recruitersInPeriod,
                    'comparison' => $this->comparison($recruitersInPeriod, $previousRecruiters),
                ],
            ],
            'attention' => [
                'jobs_without_applications' => $jobsWithoutApplications,
            ],
            'growth' => [
                'aggregation' => $aggregation,
                'labels' => $buckets['labels'],
                'series' => $growthSeries,
            ],
            'activity' => [
                'labels' => $buckets['labels'],
                'series' => $activitySeries,
            ],
            'marketplace' => $this->marketplaceHealth(
                $period['start'],
                $period['end'],
                $liveJobCount,
                $jobsWithoutApplications,
            ),
            'recent_activity' => $this->recentActivity(),
            'system_health' => $this->systemHealth(),
        ];
    }

    /**
     * @return array<string, CarbonImmutable>
     */
    private function period(int $range): array
    {
        $end = CarbonImmutable::now()->endOfDay();
        $start = $end->subDays($range - 1)->startOfDay();
        $previousEnd = $start->subSecond();
        $previousStart = $previousEnd->subDays($range - 1)->startOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }

    /**
     * @return array{keys: array<int, string>, labels: array<int, string>}
     */
    private function buckets(CarbonImmutable $start, CarbonImmutable $end, string $aggregation): array
    {
        $cursor = $aggregation === 'month' ? $start->startOfMonth() : $start->startOfDay();
        $last = $aggregation === 'month' ? $end->startOfMonth() : $end->startOfDay();
        $keys = [];
        $labels = [];

        while ($cursor->lessThanOrEqualTo($last)) {
            $key = $aggregation === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $keys[] = $key;
            $labels[] = $cursor->translatedFormat($aggregation === 'month' ? 'M Y' : 'M j');
            $cursor = $aggregation === 'month' ? $cursor->addMonth() : $cursor->addDay();
        }

        return ['keys' => $keys, 'labels' => $labels];
    }

    /**
     * @param  array<string, CarbonImmutable>  $period
     * @param  array{keys: array<int, string>, labels: array<int, string>}  $buckets
     * @return array<int, int>
     */
    private function groupedSeries(Builder $query, string $column, array $period, array $buckets, string $aggregation): array
    {
        $expression = $this->bucketExpression($column, $aggregation);
        $counts = $query
            ->whereBetween($column, [$period['start'], $period['end']])
            ->selectRaw("{$expression} as bucket, COUNT(*) as aggregate")
            ->groupByRaw($expression)
            ->pluck('aggregate', 'bucket')
            ->mapWithKeys(fn (mixed $value, mixed $key): array => [(string) $key => (int) $value])
            ->all();

        return array_map(
            fn (string $key): int => $counts[$key] ?? 0,
            $buckets['keys'],
        );
    }

    private function bucketExpression(string $column, string $aggregation): string
    {
        $driver = DB::connection()->getDriverName();

        if ($aggregation === 'month') {
            return match ($driver) {
                'sqlite' => "strftime('%Y-%m', {$column})",
                'pgsql' => "to_char({$column}, 'YYYY-MM')",
                default => "DATE_FORMAT({$column}, '%Y-%m')",
            };
        }

        return match ($driver) {
            'sqlite' => "date({$column})",
            default => "DATE({$column})",
        };
    }

    private function countBetween(Builder $query, string $column, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return (clone $query)->whereBetween($column, [$start, $end])->count();
    }

    private function registeredUsersQuery(): Builder
    {
        return User::query()->whereDoesntHave('roles', function (Builder $roles): void {
            $roles->where('name', 'Admin');
        });
    }

    private function candidatesQuery(): Builder
    {
        return User::query()->whereHas('roles', function (Builder $roles): void {
            $roles->where('name', 'Candidate');
        });
    }

    private function recruitersQuery(): Builder
    {
        return User::query()->whereHas('roles', function (Builder $roles): void {
            $roles->where('name', 'Recruiter');
        });
    }

    private function jobsWithoutApplicationsCount(): int
    {
        return Job::published()
            ->where('published_at', '<=', CarbonImmutable::now()->subDays(7))
            ->whereDoesntHave('applications')
            ->count();
    }

    /**
     * @return array<string, float|int|null>
     */
    private function marketplaceHealth(
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        int $liveJobCount,
        int $jobsWithoutApplications,
    ): array {
        $liveApplications = Application::query()
            ->whereHas('job', fn (Builder $jobs): Builder => $jobs->published())
            ->count();
        $candidateRegistrations = $this->countBetween($this->candidatesQuery(), 'created_at', $periodStart, $periodEnd);
        $activatedCandidates = $this->candidatesQuery()
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereHas('applications')
            ->count();
        $activatedRecruiters = $this->recruitersQuery()
            ->whereHas('jobs', fn (Builder $jobs): Builder => $jobs->published())
            ->count();
        $recruiterCount = $this->recruitersQuery()->count();

        return [
            'average_applications_per_live_job' => $liveJobCount > 0
                ? round($liveApplications / $liveJobCount, 1)
                : null,
            'jobs_without_applications' => $jobsWithoutApplications,
            'candidate_activation' => $candidateRegistrations > 0
                ? round(($activatedCandidates / $candidateRegistrations) * 100, 1)
                : null,
            'recruiter_activation' => $recruiterCount > 0
                ? round(($activatedRecruiters / $recruiterCount) * 100, 1)
                : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(): array
    {
        $events = [];

        $users = $this->registeredUsersQuery()
            ->with(['roles', 'company'])
            ->latest('created_at')
            ->limit(self::ACTIVITY_LIMIT_PER_SOURCE)
            ->get();

        foreach ($users as $user) {
            $isRecruiter = $user->roles->contains('name', 'Recruiter');
            $events[] = [
                'kind' => $isRecruiter ? 'recruiter_joined' : 'candidate_joined',
                'actor' => $isRecruiter ? ($user->company?->name ?? $user->name) : $user->name,
                'detail' => null,
                'occurred_at' => $user->created_at,
                'job_id' => null,
            ];
        }

        $jobs = Job::query()
            ->whereNotNull('published_at')
            ->with('company')
            ->latest('published_at')
            ->limit(self::ACTIVITY_LIMIT_PER_SOURCE)
            ->get();

        foreach ($jobs as $job) {
            $events[] = [
                'kind' => 'job_published',
                'actor' => $job->company?->name ?? $job->title,
                'detail' => $job->title,
                'occurred_at' => $job->published_at,
                'job_id' => $job->id,
            ];
        }

        $applications = Application::query()
            ->with(['candidate', 'job'])
            ->latest('created_at')
            ->limit(self::ACTIVITY_LIMIT_PER_SOURCE)
            ->get();

        foreach ($applications as $application) {
            $events[] = [
                'kind' => 'application_submitted',
                'actor' => $application->candidate?->name ?? __('admin.unknown_user'),
                'detail' => $application->job?->title,
                'occurred_at' => $application->created_at,
                'job_id' => $application->job?->id,
            ];
        }

        return collect($events)
            ->sortByDesc('occurred_at')
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function systemHealth(): array
    {
        $health = [
            [
                'key' => 'application',
                'status' => 'available',
                'value' => null,
            ],
        ];

        try {
            DB::select('select 1');
            $health[] = [
                'key' => 'database',
                'status' => 'healthy',
                'value' => null,
            ];
        } catch (Throwable) {
            $health[] = [
                'key' => 'database',
                'status' => 'unavailable',
                'value' => null,
            ];
        }

        if (Schema::hasTable('failed_jobs')) {
            try {
                $failedJobs = (int) DB::table('failed_jobs')->count();
                $health[] = [
                    'key' => 'failed_jobs',
                    'status' => $failedJobs > 0 ? 'error' : 'clear',
                    'value' => $failedJobs,
                ];
            } catch (Throwable) {
                $health[] = [
                    'key' => 'failed_jobs',
                    'status' => 'unavailable',
                    'value' => null,
                ];
            }
        }

        return $health;
    }

    /**
     * @return array{percentage: float, direction: string}|null
     */
    private function comparison(int $current, int $previous): ?array
    {
        if ($previous === 0) {
            return null;
        }

        $percentage = (($current - $previous) / $previous) * 100;

        return [
            'percentage' => round($percentage, 1),
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'flat'),
        ];
    }
}
