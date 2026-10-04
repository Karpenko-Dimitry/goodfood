<?php

namespace App\Jobs\Concerns;

use Illuminate\Queue\Jobs\SyncJob;
use Throwable;

/**
 * Secondary AI jobs (dish, photo) must never break the job that dispatched them.
 * Instead of throwing: retry later on a real queue, otherwise mark failed (runs failed()).
 */
trait RetriesOrFails
{
    protected function retryOrFail(Throwable $e): void
    {
        if ($this->job && ! $this->job instanceof SyncJob && $this->attempts() < $this->tries) {
            $this->release(30);

            return;
        }

        $this->fail($e);
    }
}
