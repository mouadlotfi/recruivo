<?php

namespace App\Http\Controllers\Recruiter;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

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

        return view('recruiter.dashboard', compact(
            'totalJobs',
            'activeJobs',
            'totalApplications',
            'pendingApplications',
            'recentApplications'
        ));
    }
}

