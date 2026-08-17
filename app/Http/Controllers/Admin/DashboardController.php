<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, AdminDashboardService $dashboardService): Response
    {
        $range = $dashboardService->normalizeRange($request->query('range'));

        return Inertia::render('Admin/Dashboard', [
            'dashboard' => fn (): array => $this->serializeDashboard($dashboardService->build($range)),
            'labels' => $this->labels(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    private function serializeDashboard(array $dashboard): array
    {
        $period = $dashboard['period'];

        return [
            'range' => $dashboard['range'],
            'period' => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
                'label' => $period['start']->translatedFormat('M j, Y').' – '.$period['end']->translatedFormat('M j, Y'),
            ],
            'metrics' => $dashboard['metrics'],
            'attention' => $dashboard['attention']['jobs_without_applications'] > 0
                ? [[
                    'key' => 'jobs_without_applications',
                    'count' => $dashboard['attention']['jobs_without_applications'],
                    'action_url' => localized_route('jobs.index'),
                ]]
                : [],
            'growth' => $dashboard['growth'],
            'activity' => $dashboard['activity'],
            'marketplace' => $dashboard['marketplace'],
            'recentActivity' => collect($dashboard['recent_activity'])
                ->map(function (array $event): array {
                    return [
                        'kind' => $event['kind'],
                        'event_label' => __('admin.activity_'.$event['kind']),
                        'actor' => $event['actor'],
                        'detail' => $event['detail'] ?? __('admin.account_created'),
                        'occurred_at' => $event['occurred_at']?->toIso8601String(),
                        'time_label' => $event['occurred_at']?->diffForHumans() ?? '',
                        'url' => $event['job_id']
                            ? localized_route('jobs.show', ['job' => $event['job_id']])
                            : null,
                    ];
                })
                ->values()
                ->all(),
            'systemHealth' => collect($dashboard['system_health'])
                ->map(fn (array $health): array => [
                    ...$health,
                    'label' => __('admin.health_'.$health['key']),
                    'status_label' => __('admin.health_status_'.$health['status']),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function labels(): array
    {
        $keys = [
            'title', 'subtitle', 'range', 'last_7_days', 'last_30_days', 'last_90_days', 'last_year',
            'users', 'users_period', 'live_jobs', 'jobs_period', 'applications', 'applications_period',
            'recruiters', 'recruiters_period', 'no_comparison', 'compared_to_previous',
            'needs_attention', 'nothing_needs_attention', 'jobs_without_applications',
            'jobs_without_applications_description', 'view_jobs', 'platform_growth', 'growth_help',
            'metric_users', 'metric_jobs', 'metric_applications', 'metric_recruiters', 'no_chart_data',
            'marketplace_health', 'average_applications_per_live_job', 'candidate_activation',
            'recruiter_activation', 'no_data', 'platform_activity', 'activity_help',
            'activity_applications', 'activity_jobs', 'activity_users', 'recent_activity',
            'no_recent_activity', 'event', 'user', 'details', 'time', 'account_created',
            'system_health', 'health_application', 'health_database', 'health_failed_jobs',
            'health_status_available', 'health_status_healthy', 'health_status_unavailable',
            'health_status_error', 'health_status_clear', 'sidebar_overview', 'sidebar_management',
            'sidebar_users', 'admin_area', 'loading',
        ];

        return collect($keys)
            ->mapWithKeys(fn (string $key): array => [$key => __('admin.'.$key)])
            ->all();
    }
}
