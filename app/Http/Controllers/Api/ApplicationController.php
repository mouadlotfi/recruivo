<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ApplicationStatusUpdatedNotification;
use App\Notifications\NewApplicationNotification;
use App\Services\DemoAccountGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $request, $jobId, DemoAccountGuard $demoAccountGuard): JsonResponse
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole('Candidate'), 403, 'Only candidates can apply to jobs.');

        $demoAccountGuard->ensureCanApply($user);

        $job = Job::with('recruiter')->findOrFail($jobId);

        abort_unless($job->isPubliclyVisible(), 404);

        $data = $request->validated();

        if (Application::where('candidate_id', $user->id)->where('job_id', $job->id)->exists()) {
            return response()->json([
                'message' => 'You have already applied to this job.',
                'errors' => [
                    'application' => ['You can only apply to each job once.'],
                ],
            ], 422);
        }

        $resumePath = null;

        if ($request->input('use_existing_resume') === 'true') {
            $resumePath = $user->candidateProfile?->resume_path;
        } elseif ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'private');
        } else {
            $resumePath = $user->candidateProfile?->resume_path;
        }

        if (! $resumePath) {
            return response()->json([
                'message' => 'A resume is required to submit an application.',
                'errors' => [
                    'resume' => ['Please upload a resume before applying to this job.'],
                ],
            ], 422);
        }

        $application = Application::firstOrCreate(
            ['candidate_id' => $user->id, 'job_id' => $job->id],
            [
                'resume_path' => $resumePath,
                'cover_letter' => $data['cover_letter'] ?? null,
                'original_status' => 'pending',
            ]
        );

        if (! $application->wasRecentlyCreated) {
            if ($request->hasFile('resume')) {
                Storage::disk('private')->delete($resumePath);
            }

            return response()->json([
                'message' => 'You have already applied to this job.',
                'errors' => [
                    'application' => ['You can only apply to each job once.'],
                ],
            ], 422);
        }

        $application->load(['job.company', 'candidate']);

        collect([$job->recruiter])
            ->merge($job->company?->recruiters ?? collect())
            ->filter()
            ->unique('id')
            ->each(function (User $recruiter) use ($application) {
                $recruiter->notify(new NewApplicationNotification($application));
            });

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
        $previousStatus = $application->status;

        $application->applyStatusUpdate($data);

        if ($previousStatus !== $application->status) {
            $application->candidate->notify(new ApplicationStatusUpdatedNotification($application));
        }

        return response()->noContent();
    }
}
