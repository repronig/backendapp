<?php

namespace App\Jobs\Monitoring;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorQueueFailuresJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            return;
        }

        $failedCount = DB::table('failed_jobs')->count();

        if ($failedCount > 0) {
            Log::warning('Queue failure monitor detected failed jobs.', ['failed_jobs_count' => $failedCount]);
        }
    }
}
