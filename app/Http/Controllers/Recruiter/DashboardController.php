<?php

namespace App\Http\Controllers\Recruiter;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return redirect(localized_route('profile.edit'))->with('error', __('recruiter.company_required'));
        }

        $totalJobs = $company->jobs()->count();
        $activeJobs = $company->jobs()->published()->count();
        $companyApplications = Application::query()->whereHas(
            'job',
            fn ($query) => $query->where('company_id', $company->id)
        );
        $totalApplications = (clone $companyApplications)->count();
        $pendingApplications = (clone $companyApplications)
            ->where('status', ApplicationStatus::Pending->value)
            ->count();

        $recentApplications = Application::whereHas('job', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })
            ->with(['candidate', 'job'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Recruiter/Dashboard', [
            'stats' => [
                'totalJobs' => $totalJobs,
                'activeJobs' => $activeJobs,
                'totalApplications' => $totalApplications,
                'pendingApplications' => $pendingApplications,
            ],
            'recentApplications' => $recentApplications->map(fn (Application $application) => [
                'id' => $application->id,
                'status' => $application->status->value,
                'status_label' => __('recruiter.'.$application->status->value),
                'created_at' => $application->created_at?->toIso8601String(),
                'created_at_label' => $application->created_at?->diffForHumans() ?? '',
                'candidate' => [
                    'name' => $application->candidate->name,
                    'initial' => substr($application->candidate->name, 0, 1),
                ],
                'job' => [
                    'id' => $application->job->id,
                    'title' => $application->job->title,
                ],
            ])->values()->all(),
            'labels' => [
                'dashboard' => __('recruiter.dashboard'),
                'dashboard_subtitle' => __('recruiter.dashboard_subtitle'),
                'post_new_job' => __('recruiter.post_new_job'),
                'total_jobs' => __('recruiter.total_jobs'),
                'active_jobs' => __('recruiter.active_jobs'),
                'total_applications' => __('recruiter.total_applications'),
                'pending_applications' => __('recruiter.pending_applications'),
                'recent_applications' => __('recruiter.recent_applications'),
                'view_all_jobs' => __('recruiter.view_all_jobs'),
                'no_applications_yet' => __('recruiter.no_applications_yet'),
                'applications_will_appear' => __('recruiter.applications_will_appear'),
                'pending' => __('recruiter.pending'),
                'shortlisted' => __('recruiter.shortlisted'),
                'interview' => __('recruiter.interview'),
                'accepted' => __('recruiter.accepted'),
                'rejected' => __('recruiter.rejected'),
                'withdrawn' => __('recruiter.withdrawn'),
                'view' => __('recruiter.view'),
                'quick_actions' => __('recruiter.quick_actions'),
                'manage_jobs' => __('recruiter.manage_jobs'),
            ],
        ]);
    }
}

