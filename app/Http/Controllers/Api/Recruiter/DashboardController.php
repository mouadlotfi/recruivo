<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobs = $request->user()->jobs()->withCount([
            'applications as pending_applications_count' => function ($query) {
                $query->where('status', ApplicationStatus::Pending->value);
            },
            'applications',
        ])->latest()->paginate(10);

        $jobsBuilder = $request->user()->jobs();

        $metrics = [
            'active' => (clone $jobsBuilder)->published()->count(),
            'drafts' => (clone $jobsBuilder)->where('status', 'draft')->count(),
            'total_applicants' => (clone $jobsBuilder)->withCount('applications')->get()->sum('applications_count'),
        ];

        return response()->json([
            'data' => [
                'jobs' => $jobs,
                'metrics' => $metrics,
            ]
        ]);
    }
}
