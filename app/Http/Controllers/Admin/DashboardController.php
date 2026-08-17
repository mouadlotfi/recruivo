<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalJobs = Job::count();
        $totalApplications = Application::count();
        $totalCompanies = Company::count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => $totalUsers,
                'jobs' => $totalJobs,
                'applications' => $totalApplications,
                'companies' => $totalCompanies,
            ],
            'labels' => [
                'title' => __('admin.dashboard'),
                'subtitle' => __('admin.dashboard_subtitle'),
                'manage_users' => __('admin.manage_users'),
                'user_management' => __('admin.user_management'),
                'view_all_users' => __('admin.view_all_users'),
                'system_overview' => __('admin.system_overview'),
                'total_users' => __('admin.total_users'),
                'total_jobs' => __('admin.total_jobs'),
                'total_applications' => __('admin.total_applications'),
                'total_companies' => __('admin.total_companies'),
                'active_users' => __('admin.active_users'),
                'published_jobs' => __('admin.published_jobs'),
                'registered_companies' => __('admin.registered_companies'),
                'system_status' => __('admin.system_status'),
                'system_online' => __('admin.system_online'),
                'all_services_running' => __('admin.all_services_running'),
                'performance' => __('admin.performance'),
                'optimal_response_times' => __('admin.optimal_response_times'),
                'security' => __('admin.security'),
                'all_systems_secure' => __('admin.all_systems_secure'),
            ],
        ]);
    }
}

