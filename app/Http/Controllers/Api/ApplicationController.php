<?php

namespace App\Http\Controllers\Api;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Job;
use App\Notifications\ApplicationStatusUpdatedNotification;
use App\Notifications\NewApplicationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $request, $jobId): JsonResponse
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole('Candidate'), 403, 'Only candidates can apply to jobs.');

        $job = Job::with('recruiter')->findOrFail($jobId);

        abort_unless($job->status === JobStatus::Published, 404);

        $data = $request->validated();

        $resumePath = null;
        
        // Check if user wants to use existing resume
        if ($request->input('use_existing_resume') === 'true') {
            $resumePath = $user->candidateProfile?->resume_path;
        }
        
        // If no existing resume or user uploaded a new one, use the uploaded file
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
        }
        
        // If still no resume path, try to use existing one as fallback
        if (!$resumePath) {
            $resumePath = $user->candidateProfile?->resume_path;
        }

        if (!$resumePath) {
            return response()->json([
                'message' => 'A resume is required to submit an application.',
                'errors' => [
                    'resume' => ['Please upload a resume before applying to this job.'],
                ],
            ], 422);
        }

        // Check if user has already applied to this job
        $existingApplication = Application::where('candidate_id', $user->id)
            ->where('job_id', $job->id)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already applied to this job.',
                'errors' => [
                    'application' => ['You can only apply to each job once.'],
                ],
            ], 422);
        }

        $application = Application::create([
            'candidate_id' => $user->id,
            'job_id' => $job->id,
            'resume_path' => $resumePath,
            'cover_letter' => $data['cover_letter'] ?? null,
            'original_status' => 'pending',
        ])->load(['job.company', 'candidate']);

        if ($job->recruiter) {
            $job->recruiter->notify(new NewApplicationNotification($application));
        }

        return (new ApplicationResource($application))
            ->additional(['message' => 'Application submitted successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    public function index(): AnonymousResourceCollection
    {
        $applications = Application::whereHas('job', function ($query) {
            $query->where('recruiter_id', auth()->id());
        })->with(['job', 'candidate'])->paginate();

        return ApplicationResource::collection($applications);
    }

    public function update(UpdateApplicationStatusRequest $request, Application $application): Response
    {
        abort_unless($application->job->recruiter_id === auth()->id(), 403);

        $data = $request->validated();

        $application->applyStatusUpdate($data);
        $application->candidate->notify(new ApplicationStatusUpdatedNotification($application));

        return response()->noContent();
    }
}
