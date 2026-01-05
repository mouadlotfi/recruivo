<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Job;
use App\Notifications\ApplicationStatusUpdatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function index(Request $request, Job $job): AnonymousResourceCollection
    {
        $this->authorizeJob($job);

        $applications = $job->applications()
            ->with('candidate')
            ->latest()
            ->paginate(12);

        return ApplicationResource::collection($applications);
    }

    public function update(UpdateApplicationStatusRequest $request, Application $application): JsonResponse
    {
        $job = $application->job;
        $this->authorizeJob($job);

        $data = $request->validated();

        $application->applyStatusUpdate($data);
        $application->loadMissing(['candidate', 'job.company']);
        $application->candidate->notify(new ApplicationStatusUpdatedNotification($application));

        return response()->json([
            'message' => 'Application updated successfully.',
            'data' => new ApplicationResource($application)
        ]);
    }

    public function downloadResume(Request $request, Application $application)
    {
        $job = $application->job;
        $this->authorizeJob($job);

        if (!$application->resume_path || !Storage::disk('public')->exists($application->resume_path)) {
            return response()->json(['message' => 'Resume not found'], 404);
        }

        return Storage::disk('public')->download($application->resume_path);
    }

    protected function authorizeJob(Job $job): void
    {
        abort_unless($job->recruiter_id === auth()->id(), 403, 'You can only manage applications for your own jobs.');
    }
}
