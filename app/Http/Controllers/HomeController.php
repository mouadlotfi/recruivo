<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;

class HomeController extends Controller
{
    public function index()
    {
        if (auth()->user()?->hasRole('Recruiter')) {
            return redirect(localized_route('recruiter.dashboard'));
        }

        $user = auth()->user();
        $preferred = $user?->hasRole('Candidate') ? $user->candidateProfile?->preferred_categories : null;
        $hasPreferences = filled($preferred);

        $jobs = Job::published()
            ->with('company')
            ->withSavedStateFor($user)
            ->orderByPreference($preferred)
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'total_roles' => Job::published()->count(),
            'remote_roles' => Job::published()->where('remote_type', 'remote')->count(),
            'new_this_week' => Job::published()->where('published_at', '>=', now()->subWeek())->count(),
            'active_companies' => Company::whereHas('jobs', fn ($q) => $q->published())->count(),
        ];

        return view('home', compact('jobs', 'metrics', 'hasPreferences'));
    }
}

