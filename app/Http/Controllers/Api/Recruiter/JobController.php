<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Resources\JobResource;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JobController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $jobs = $request->user()->jobs()
            ->withCount('applications')
            ->latest()
            ->paginate(10);

        return JobResource::collection($jobs);
    }

    public function store(StoreJobRequest $request): JsonResponse
    {
        abort_unless($request->user()->company_id, 403, 'You must be associated with a company to create jobs.');

        $job = $request->user()->jobs()->create($this->mapJobData($request->validated(), $request));

        return response()->json([
            'message' => 'Job created successfully.',
            'data' => new JobResource($job),
        ], 201);
    }

    public function show(Request $request, Job $job): JsonResponse
    {
        $this->authorizeJob($job);

        return response()->json([
            'data' => new JobResource($job->load('company')),
        ]);
    }

    public function update(UpdateJobRequest $request, Job $job): JsonResponse
    {
        $this->authorizeJob($job);

        $job->update($this->mapJobData($request->validated(), $request, $job));

        return response()->json([
            'message' => 'Job updated successfully.',
            'data' => new JobResource($job),
        ]);
    }

    public function destroy(Job $job): JsonResponse
    {
        $this->authorizeJob($job);
        $job->delete();

        return response()->json([
            'message' => 'Job deleted successfully.',
        ]);
    }

    public function toggle(Request $request, Job $job): JsonResponse
    {
        $this->authorizeJob($job);

        if ($job->isExpired()) {
            return response()->json(['message' => 'This job is expired. Set a closing date of today or later before publishing it.'], 422);
        }

        $job->status = $job->status === JobStatus::Published ? JobStatus::Draft : JobStatus::Published;
        $job->published_at = $job->status === JobStatus::Published ? now() : null;
        $job->save();

        return response()->json([
            'message' => 'Job status updated successfully.',
            'data' => new JobResource($job),
        ]);
    }

    protected function authorizeJob(Job $job): void
    {
        abort_unless($job->recruiter_id === auth()->id(), 403, 'You can only manage your own jobs.');
    }

    /**
     * Normalise a job payload before it is persisted.
     *
     * `$job` is null when creating and the job being updated otherwise. `status`
     * is optional on update, so an update that omits it must leave `published_at`
     * untouched: deriving "not published" from a missing status cleared the
     * timestamp and silently dropped the job out of every public listing. The
     * timestamp is stamped once, on the draft -> published transition, matching
     * Recruiter\JobController::update().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mapJobData(array $data, Request $request, ?Job $job = null): array
    {
        $isPublished = ($data['status'] ?? null) === JobStatus::Published->value;

        if ($job === null) {
            // New job: a draft carries no publication date.
            $data['published_at'] = $isPublished ? now() : null;
        } elseif ($isPublished && $job->status !== JobStatus::Published) {
            $data['published_at'] = now();
        }

        $data['company_id'] = $request->user()->company_id;

        return $data;
    }
}
