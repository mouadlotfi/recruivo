<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\DemoAccountGuard;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $validStatuses = array_map(fn (ApplicationStatus $case) => $case->value, ApplicationStatus::cases());

        if ($status !== 'all' && !in_array($status, $validStatuses, true)) {
            return redirect()->route('candidate.applications', ['locale' => app()->getLocale()]);
        }

        $baseQuery = $request->user()->applications();
        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            ...collect(ApplicationStatus::cases())->mapWithKeys(
                fn (ApplicationStatus $case) => [$case->value => (clone $baseQuery)->where('status', $case->value)->count()]
            )->all(),
        ];

        $applications = $baseQuery
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with(['job.company', 'statusEvents.changedBy:id,name'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('candidate.applications', compact('applications', 'status', 'statusCounts'));
    }

    public function withdraw(Request $request, string $locale, Application $application, DemoAccountGuard $demoAccountGuard)
    {
        $this->authorize('withdraw', $application);
        $demoAccountGuard->ensureCandidateActionsAreMutable($request->user());

        $application->applyStatusUpdate(['status' => ApplicationStatus::Withdrawn->value]);

        return back()->with('success', __('applications.withdrawn_success'));
    }
}

