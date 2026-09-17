<?php

namespace App\Jobs;

use App\Console\Commands\QueueHealth;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Stamps the queue health heartbeat, from the worker.
 *
 * Only a write that happens on the worker is evidence about the worker, so this
 * has to be a queued job: `Schedule::call` runs on the scheduler, which is not
 * the thing being watched.
 *
 * It replaced a closure that dispatched a second closure, and that does not
 * survive serialization intact. Both arrow functions sat on the same expression,
 * so serializable-closure resolved the queued job back to the outer one: the job
 * dispatched itself, and the worker spun at 68% CPU on a queue that never
 * drained. A class cannot make that mistake.
 */
class QueueHeartbeat implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put(QueueHealth::HEARTBEAT_KEY, now(), now()->addDay());
    }
}
