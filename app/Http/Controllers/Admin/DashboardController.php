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
            'attention' => $this->serializeAttention($dashboard['attention']),
            'pipeline' => $this->serializePipeline($dashboard['pipeline']),
            'growth' => $dashboard['growth'],
            'activity' => $dashboard['activity'],
            'marketplace' => $dashboard['marketplace'],
            'recentActivity' => collect($dashboard['recent_activity'])
                ->map(function (array $event): array {
                    return [
                        'id' => $event['id'],
                        'kind' => $event['kind'],
                        'event_label' => __('admin.activity_'.$event['kind']),
                        'actor' => $event['actor'],
                        'detail' => $event['detail'] ?? __('admin.account_created'),
                        'occurred_at' => $event['occurred_at']?->toIso8601String(),
                        'time_label' => $event['occurred_at']?->diffForHumans() ?? '',
                        'url' => $event['job_id']
                            ? localized_route('admin.jobs', ['job' => $event['job_id']])
                            : ($event['user_search']
                                ? localized_route('admin.users', ['search' => $event['user_search']])
                                : null),
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
            'application_pipeline', 'pipeline_help',
            'marketplace_health', 'average_applications_per_live_job', 'candidate_activation',
            'marketplace_help', 'candidate_activation_help',
            'recruiters_with_live_jobs', 'no_data', 'platform_activity', 'activity_help',
            'activity_applications', 'activity_jobs', 'activity_users', 'recent_activity',
            'no_recent_activity', 'event', 'user', 'details', 'time', 'account_created',
            'activity_user', 'applications_metric',
            'system_health', 'health_application', 'health_database', 'health_failed_jobs',
            'health_status_available', 'health_status_healthy', 'health_status_unavailable',
            'health_status_error', 'health_status_clear', 'sidebar_overview', 'sidebar_management',
            'sidebar_users', 'sidebar_jobs', 'admin_area', 'loading', 'jobs_without_applications_metric',
            'failed_jobs', 'failed_jobs_description', 'requires_review',
        ];

        $labels = collect($keys)
            ->mapWithKeys(fn (string $key): array => [$key => __('admin.'.$key)])
            ->all();

        $labels['user'] = __('admin.activity_user');
        $labels['applications'] = __('admin.applications_metric');

        return $labels;
    }

    /**
     * @param  array<string, int|null>  $attention
     * @return array<int, array<string, mixed>>
     */
    private function serializeAttention(array $attention): array
    {
        $items = [];

        if (($attention['jobs_without_applications'] ?? 0) > 0) {
            $count = (int) $attention['jobs_without_applications'];
            $actionUrl = localized_route('admin.jobs', ['no_applications' => 1]);
            $actionLabel = __('admin.view_jobs');

            $items[] = [
                'key' => 'jobs_without_applications',
                'count' => $count,
                'title' => trans_choice('admin.jobs_without_applications', $count, ['count' => $count]),
                'description' => __('admin.jobs_without_applications_description'),
                'action_label' => $actionLabel,
                'action_url' => $actionUrl,
                'action' => [
                    'label' => $actionLabel,
                    'url' => $actionUrl,
                ],
                'status' => 'warning',
                'status_label' => __('admin.health_status_error'),
            ];
        }

        if (($attention['failed_jobs'] ?? 0) > 0) {
            $count = (int) $attention['failed_jobs'];
            $actionLabel = __('admin.requires_review');

            $items[] = [
                'key' => 'failed_jobs',
                'count' => $count,
                'title' => trans_choice('admin.failed_jobs', $count, ['count' => $count]),
                'description' => __('admin.failed_jobs_description'),
                'action_label' => $actionLabel,
                'action_url' => null,
                'action' => [
                    'label' => $actionLabel,
                    'url' => null,
                ],
                'status' => 'requires_review',
                'status_label' => $actionLabel,
            ];
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $pipeline
     * @return array<string, mixed>
     */
    private function serializePipeline(array $pipeline): array
    {
        $period = $pipeline['period'];

        return [
            'range' => $pipeline['range'],
            'period' => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
            ],
            'status_keys' => $pipeline['status_keys'],
            'counts' => $pipeline['counts'],
            'statuses' => collect($pipeline['status_keys'])
                ->map(fn (string $status): array => [
                    'key' => $status,
                    'label' => __('applications.status_'.$status),
                    'count' => $pipeline['counts'][$status] ?? 0,
                ])
                ->values()
                ->all(),
        ];
    }
}
