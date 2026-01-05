<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalJobs = Job::count();
        $totalApplications = Application::count();
        $totalCompanies = Company::count();

        return view('admin.dashboard', compact('totalUsers', 'totalJobs', 'totalApplications', 'totalCompanies'));
    }
}

