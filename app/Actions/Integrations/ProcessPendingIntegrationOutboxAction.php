<?php

namespace App\Actions\Integrations;

use App\Contracts\DeliversIntegrationOutboxEntry;
use App\Enums\IntegrationSyncStatus;
use App\Models\IntegrationOutboxEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessPendingIntegrationOutboxAction
{
    public function __construct(
        protected DeliversIntegrationOutboxEntry $deliversIntegrationOutboxEntry
    ) {}

    public function execute(int $limit = 25): int
    {
        $processed = 0;

        $ids = IntegrationOutboxEntry::query()
            ->where('status', IntegrationSyncStatus::Pending)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$processed): void {
                /** @var IntegrationOutboxEntry|null $entry */
                $entry = IntegrationOutboxEntry::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if ($entry === null || $entry->status !== IntegrationSyncStatus::Pending) {
                    return;
                }

                $entry->update([
                    'status' => IntegrationSyncStatus::Processing,
                    'attempts' => $entry->attempts + 1,
                ]);

                try {
                    $this->deliversIntegrationOutboxEntry->deliver($entry->fresh());

                    $entry->update([
                        'status' => IntegrationSyncStatus::Succeeded,
                        'processed_at' => now(),
                        'last_error' => null,
                    ]);

                    Log::info('integration_outbox_delivery_outcome', [
                        'outbox_id' => $entry->id,
                        'provider' => $entry->provider->value,
                        'outcome' => 'succeeded',
                        'attempts' => $entry->attempts,
                    ]);
                } catch (\Throwable $e) {
                    $maxAttempts = max(1, (int) config('integrations.outbox.max_attempts', 5));
                    $willRetry = $entry->attempts < $maxAttempts;

                    Log::warning('integration_outbox_delivery_failed', [
                        'outbox_id' => $entry->id,
                        'attempts' => $entry->attempts,
                        'max_attempts' => $maxAttempts,
                        'will_retry' => $willRetry,
                        'error' => Str::limit($e->getMessage(), 500),
                    ]);

                    if ($willRetry) {
                        $delaySeconds = $this->retryDelaySeconds($entry->attempts);
                        $entry->update([
                            'status' => IntegrationSyncStatus::Pending,
                            'last_error' => $e->getMessage(),
                            'scheduled_at' => now()->addSeconds($delaySeconds),
                        ]);

                        Log::info('integration_outbox_delivery_outcome', [
                            'outbox_id' => $entry->id,
                            'provider' => $entry->provider->value,
                            'outcome' => 'retry_scheduled',
                            'attempts' => $entry->attempts,
                            'retry_in_seconds' => $delaySeconds,
                        ]);
                    } else {
                        $entry->update([
                            'status' => IntegrationSyncStatus::Failed,
                            'last_error' => $e->getMessage(),
                        ]);

                        Log::info('integration_outbox_delivery_outcome', [
                            'outbox_id' => $entry->id,
                            'provider' => $entry->provider->value,
                            'outcome' => 'failed_final',
                            'attempts' => $entry->attempts,
                        ]);
                    }
                }

                $processed++;
            });
        }

        return $processed;
    }

    /**
     * Exponential backoff from the first failed attempt (attempts counts in-flight tries).
     */
    private function retryDelaySeconds(int $attemptsAfterFailure): int
    {
        $base = max(1, (int) config('integrations.outbox.retry_backoff_base_seconds', 60));
        $cap = max($base, (int) config('integrations.outbox.retry_backoff_max_seconds', 3600));
        $exponent = max(0, $attemptsAfterFailure - 1);
        $delay = $base * (2 ** $exponent);

        return (int) min($cap, $delay);
    }
}
