<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Page-string keys rendered by Candidate/Dashboard.vue.
     *
     * @var array<int, string>
     */
    private const PAGE_LABEL_KEYS = [
        'dashboard', 'dashboard_subtitle', 'browse_jobs', 'browse',
        'total_applications', 'in_progress', 'accepted', 'rejected',
        'profile_completion', 'profile_completion_help', 'complete_profile',
        'completion_headline', 'completion_profile_summary', 'completion_skills',
        'completion_resume', 'completion_experience', 'profile_complete',
        'recent_applications', 'view_all_applications', 'no_applications_yet',
        'start_applying', 'view', 'quick_actions', 'browse_available_jobs',
        'view_my_applications', 'update_my_profile', 'pending', 'shortlisted',
        'interview', 'withdrawn',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        $totalApplications = $user->applications()->count();
        $inProgressApplications = $user->applications()->whereIn('status', [
            ApplicationStatus::Pending,
            ApplicationStatus::Shortlisted,
            ApplicationStatus::Interview,
        ])->count();
        $acceptedApplications = $user->applications()->where('status', ApplicationStatus::Accepted)->count();
        $rejectedApplications = $user->applications()->where('status', ApplicationStatus::Rejected)->count();

        $recentApplications = $user->applications()
            ->with(['job.company'])
            ->latest()
            ->take(5)
            ->get();

        $user->loadMissing('candidateProfile');
        $profileCompletion = $user->profileCompletion();

        return Inertia::render('Candidate/Dashboard', [
            'totalApplications' => $totalApplications,
            'inProgressApplications' => $inProgressApplications,
            'acceptedApplications' => $acceptedApplications,
            'rejectedApplications' => $rejectedApplications,
            'recentApplications' => $recentApplications
                ->map(fn (Application $application) => $this->serializeRecentApplication($application))
                ->values()
                ->all(),
            'profileCompletion' => $profileCompletion,
            'labels' => collect(self::PAGE_LABEL_KEYS)
                ->mapWithKeys(fn (string $key) => [$key => __("candidate.$key")])
                ->all(),
        ]);
    }

    /**
     * Serialize only the fields rendered by the dashboard's recent applications list.
     *
     * @return array<string, mixed>
     */
    private function serializeRecentApplication(Application $application): array
    {
        $job = $application->job;
        $company = $job->company;
        $status = $application->status->value;

        return [
            'id' => $application->id,
            'status' => $status,
            'status_label' => __("candidate.$status"),
            'applied_label' => $application->created_at?->diffForHumans(),
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $company ? ['name' => $company->name] : null,
            ],
        ];
    }
}
