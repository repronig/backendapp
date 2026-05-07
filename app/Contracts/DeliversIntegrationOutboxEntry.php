<?php

namespace App\Contracts;

use App\Models\IntegrationOutboxEntry;

interface DeliversIntegrationOutboxEntry
{
    /**
     * Perform outbound delivery for a single outbox row (transport-specific).
     *
     * @throws \Throwable When the row should be marked failed (or retried by the processor).
     */
    public function deliver(IntegrationOutboxEntry $entry): void;
}
