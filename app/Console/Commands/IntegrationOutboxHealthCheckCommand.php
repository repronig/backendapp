<?php

namespace App\Console\Commands;

use App\Enums\IntegrationSyncStatus;
use App\Models\IntegrationOutboxEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IntegrationOutboxHealthCheckCommand extends Command
{
    protected $signature = 'integrations:outbox-health';

    protected $description = 'Log a critical alert when failed integration outbox rows exceed the configured threshold in the lookback window.';

    public function handle(): int
    {
        $hours = (int) config('integrations.outbox.health_failed_window_hours', 24);
        $threshold = (int) config('integrations.outbox.health_failed_threshold', 25);

        $count = IntegrationOutboxEntry::query()
            ->where('status', IntegrationSyncStatus::Failed)
            ->where('updated_at', '>=', now()->subHours($hours))
            ->count();

        if ($count >= $threshold) {
            Log::critical('integration_outbox_failed_spike', [
                'failed_count' => $count,
                'window_hours' => $hours,
                'threshold' => $threshold,
            ]);
            $this->warn("Failed outbox rows ({$count}) reached or exceeded threshold ({$threshold}) in the last {$hours} hour(s).");
        } else {
            $this->info("Outbox health OK: {$count} failed row(s) in the last {$hours} hour(s) (threshold {$threshold}).");
        }

        return self::SUCCESS;
    }
}
