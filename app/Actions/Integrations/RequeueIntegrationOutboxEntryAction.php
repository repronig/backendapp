<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationSyncStatus;
use App\Models\IntegrationOutboxEntry;
use InvalidArgumentException;

class RequeueIntegrationOutboxEntryAction
{
    public function execute(IntegrationOutboxEntry $entry): IntegrationOutboxEntry
    {
        if ($entry->status !== IntegrationSyncStatus::Failed) {
            throw new InvalidArgumentException('Only failed outbox entries can be requeued.');
        }

        $entry->update([
            'status' => IntegrationSyncStatus::Pending,
            'attempts' => 0,
            'last_error' => null,
            'scheduled_at' => null,
            'processed_at' => null,
        ]);

        return $entry->fresh();
    }
}
