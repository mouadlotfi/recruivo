<?php

namespace App\Http\Controllers\Recruiter;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return redirect(localized_route('home'))->with('error', __('recruiter.company_required'));
        }

        $totalJobs = $company->jobs()->count();
        $activeJobs = $company->jobs()->published()->count();
        $totalApplications = $company->jobs()->withCount('applications')->get()->sum('applications_count');
        $pendingApplications = $company->jobs()
            ->with(['applications' => function ($query) {
                $query->where('status', ApplicationStatus::Pending);
            }])
            ->get()
            ->sum(function ($job) {
                return $job->applications->count();
            });

        $recentApplications = \App\Models\Application::whereHas('job', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })
            ->with(['candidate', 'job'])
            ->latest()
            ->take(5)
            ->get();

        return view('recruiter.dashboard', compact(
            'totalJobs',
            'activeJobs',
            'totalApplications',
            'pendingApplications',
            'recentApplications'
        ));
    }
}

