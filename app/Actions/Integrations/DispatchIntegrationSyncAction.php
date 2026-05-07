<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationProvider;
use App\Jobs\Integrations\ProcessIntegrationOutboxJob;
use App\Models\ExternalIntegration;

/**
 * Dispatches async processing for integration outbox when the provider is enabled.
 */
class DispatchIntegrationSyncAction
{
    public function execute(IntegrationProvider $provider): bool
    {
        $enabled = ExternalIntegration::query()
            ->where('provider', $provider)
            ->where('is_enabled', true)
            ->exists();

        if (! $enabled) {
            return false;
        }

        ProcessIntegrationOutboxJob::dispatch();

        return true;
    }
}
