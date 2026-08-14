<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $applicants = User::query()
            ->whereHas('applications.job', fn ($query) => $query->where('company_id', $companyId))
            ->with(['applications' => fn ($query) => $query
                ->whereHas('job', fn ($jobQuery) => $jobQuery->where('company_id', $companyId))
                ->with('job')
                ->latest(), 'candidateProfile'])
            ->withCount(['applications as applications_count' => fn ($query) => $query
                ->whereHas('job', fn ($jobQuery) => $jobQuery->where('company_id', $companyId))])
            ->orderBy('name')
            ->paginate(20);

        return view('recruiter.applicants.index', compact('applicants'));
    }

    public function show(Request $request, string $locale, User $applicant)
    {
        $companyId = $request->user()->company_id;
        $applications = Application::query()
            ->where('candidate_id', $applicant->id)
            ->whereHas('job', fn ($query) => $query->where('company_id', $companyId))
            ->with('job')
            ->latest()
            ->get();

        abort_if($applications->isEmpty(), 404);

        $applicant->load('candidateProfile');

        return view('recruiter.applicants.show', compact('applicant', 'applications'));
    }
}
