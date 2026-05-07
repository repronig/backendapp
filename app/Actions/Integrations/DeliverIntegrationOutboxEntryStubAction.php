<?php

namespace App\Actions\Integrations;

use App\Contracts\DeliversIntegrationOutboxEntry;
use App\Models\IntegrationOutboxEntry;

/**
 * No-op transport for tests and environments where outbound HTTP is disabled.
 */
class DeliverIntegrationOutboxEntryStubAction implements DeliversIntegrationOutboxEntry
{
    public function deliver(IntegrationOutboxEntry $entry): void
    {
        // Caller marks the outbox row succeeded after this returns.
    }
}
