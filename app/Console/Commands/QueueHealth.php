<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * Fails when queued work is not moving, so the schedule's email-on-failure has
 * something to send.
 *
 * Queued mail is how a candidate's application reaches a recruiter, and a queue
 * that has stopped looks exactly like a quiet one: nothing errors, nothing
 * arrives, nobody notices. These are the three ways that happens, checked in
 * order of how directly they explain themselves.
 */
class QueueHealth extends Command
{
    /**
     * Stamped by the heartbeat the scheduler dispatches - the write happens on
     * the worker, which is the thing being watched. Public so routes/console.php
     * and this command cannot drift apart on the key.
     */
    public const HEARTBEAT_KEY = 'queue-health:heartbeat';

    /**
     * The highest failed-job id already reported, so one failure is reported once
     * rather than every run until somebody clears it. An id rather than a
     * timestamp: failed_at has second precision and the query builder truncates
     * its bindings to match, so a failure landing in the same second as the check
     * compares equal to it and would be reported forever.
     */
    private const REPORTED_KEY = 'queue-health:last-reported-id';

    protected $signature = 'queue:health
                            {--max-pending=100 : Fail when more than this many jobs are waiting}
                            {--max-heartbeat-age=20 : Fail when the worker has not run for this many minutes}';

    protected $description = 'Fail when queued work has stopped moving, or when jobs have failed';

    public function handle(): int
    {
        $pending = Queue::size();
        $maxPending = (int) $this->option('max-pending');
        $heartbeatAge = $this->heartbeatAgeInMinutes();
        $maxHeartbeatAge = (int) $this->option('max-heartbeat-age');
        $failures = $this->failuresSinceLastCheck();

        $problems = [];

        if ($failures > 0) {
            $problems[] = "{$failures} queued job(s) failed since the last check.";
        }

        if ($pending > $maxPending) {
            $problems[] = "{$pending} job(s) are waiting, above the {$maxPending} threshold.";
        }

        if ($heartbeatAge === null) {
            $problems[] = 'No heartbeat has been recorded, so the worker has not run since this check last restarted.';
        } elseif ($heartbeatAge > $maxHeartbeatAge) {
            $problems[] = "The worker last ran {$heartbeatAge} minutes ago, past the {$maxHeartbeatAge} minute limit.";
        }

        // Advanced either way. It marks what has been looked at, not what was fine.
        $this->markReported();

        if ($problems === []) {
            $this->info("Queue healthy: {$pending} waiting, no new failures, worker ran {$heartbeatAge} minute(s) ago.");

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->error($problem);
        }

        return self::FAILURE;
    }

    /**
     * Failures that landed since the previous check. A job that fails while this
     * command is running gets a higher id than the marker, so it is picked up on
     * the next run rather than lost between the two.
     */
    private function failuresSinceLastCheck(): int
    {
        return DB::table('failed_jobs')
            ->where('id', '>', (int) Cache::get(self::REPORTED_KEY, 0))
            ->count();
    }

    private function markReported(): void
    {
        $highest = DB::table('failed_jobs')->max('id');

        Cache::put(self::REPORTED_KEY, (int) ($highest ?? 0), now()->addWeek());
    }

    private function heartbeatAgeInMinutes(): ?int
    {
        $heartbeat = Cache::get(self::HEARTBEAT_KEY);

        if (! $heartbeat instanceof Carbon) {
            return null;
        }

        return (int) $heartbeat->diffInMinutes(now());
    }
}
