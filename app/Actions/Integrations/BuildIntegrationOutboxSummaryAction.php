<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationSyncStatus;
use App\Models\IntegrationOutboxEntry;

class BuildIntegrationOutboxSummaryAction
{
    /**
     * @return array{
     *     pending_total: int,
     *     failed_last_24h: int,
     *     processing_total: int,
     *     oldest_pending_created_at: string|null,
     *     oldest_pending_scheduled_at: string|null,
     * }
     */
    public function execute(): array
    {
        $hours = (int) config('integrations.outbox.health_failed_window_hours', 24);

        $pendingTotal = IntegrationOutboxEntry::query()
            ->where('status', IntegrationSyncStatus::Pending)
            ->count();

        $processingTotal = IntegrationOutboxEntry::query()
            ->where('status', IntegrationSyncStatus::Processing)
            ->count();

        $failedLast24h = IntegrationOutboxEntry::query()
            ->where('status', IntegrationSyncStatus::Failed)
            ->where('updated_at', '>=', now()->subHours($hours))
            ->count();

        $oldestPending = IntegrationOutboxEntry::query()
            ->where('status', IntegrationSyncStatus::Pending)
            ->orderBy('created_at')
            ->first();

        return [
            'pending_total' => $pendingTotal,
            'processing_total' => $processingTotal,
            'failed_last_24h' => $failedLast24h,
            'oldest_pending_created_at' => optional($oldestPending?->created_at)->toIso8601String(),
            'oldest_pending_scheduled_at' => optional($oldestPending?->scheduled_at)->toIso8601String(),
        ];
    }
}
