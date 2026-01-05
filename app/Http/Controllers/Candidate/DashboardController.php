<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $totalApplications = $user->applications()->count();
        $pendingApplications = $user->applications()->where('status', ApplicationStatus::Pending)->count();
        $acceptedApplications = $user->applications()->where('status', ApplicationStatus::Accepted)->count();
        $rejectedApplications = $user->applications()->where('status', ApplicationStatus::Rejected)->count();

        $recentApplications = $user->applications()
            ->with(['job.company'])
            ->latest()
            ->take(5)
            ->get();

        return view('candidate.dashboard', compact(
            'totalApplications',
            'pendingApplications',
            'acceptedApplications',
            'rejectedApplications',
            'recentApplications'
        ));
    }
}

