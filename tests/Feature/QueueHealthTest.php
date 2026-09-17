<?php

namespace Tests\Feature;

use App\Console\Commands\QueueHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Queued mail is how a candidate's application reaches a recruiter. A queue that
 * has stopped looks exactly like a quiet one, so the only way anyone finds out is
 * a check that fails on purpose and lets the scheduler mail its output.
 *
 * The interesting property is the second half: a failure is reported once, not
 * hourly until somebody clears it, and a new failure after a healthy check is
 * reported again. An alert that repeats gets filtered, and the one that matters
 * gets filtered with it.
 */
class QueueHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Nothing is wrong unless a test makes it so.
        $this->beat();
    }

    public function test_a_working_queue_is_healthy(): void
    {
        $this->artisan('queue:health')->assertSuccessful();
    }

    public function test_a_failed_job_is_reported(): void
    {
        $this->failJob();

        $this->artisan('queue:health')->assertFailed();
    }

    public function test_the_same_failure_is_not_reported_twice(): void
    {
        $this->failJob();

        $this->artisan('queue:health')->assertFailed();
        $this->artisan('queue:health')->assertSuccessful();
    }

    public function test_a_failure_after_a_healthy_check_is_reported_again(): void
    {
        $this->artisan('queue:health')->assertSuccessful();

        $this->failJob();

        $this->artisan('queue:health')->assertFailed();
    }

    public function test_a_worker_that_has_never_run_is_reported(): void
    {
        Cache::forget(QueueHealth::HEARTBEAT_KEY);

        $this->artisan('queue:health')->assertFailed();
    }

    public function test_a_worker_that_has_stopped_is_reported(): void
    {
        Cache::put(QueueHealth::HEARTBEAT_KEY, now()->subHour(), now()->addDay());

        $this->artisan('queue:health')->assertFailed();
    }

    public function test_a_backlog_is_reported(): void
    {
        Queue::shouldReceive('size')->andReturn(150);

        $this->artisan('queue:health')->assertFailed();
    }

    public function test_the_thresholds_can_be_tuned_from_the_schedule(): void
    {
        Queue::shouldReceive('size')->andReturn(150);

        $this->artisan('queue:health', ['--max-pending' => 200])->assertSuccessful();
    }

    private function beat(): void
    {
        Cache::put(QueueHealth::HEARTBEAT_KEY, now(), now()->addDay());
    }

    private function failJob(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'test',
            'failed_at' => now(),
        ]);
    }
}
