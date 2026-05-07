<?php

namespace App\Services\Sync;

use App\Contracts\OutboundSyncPublisher;
use Illuminate\Support\Facades\Log;

class NullOutboundSyncPublisher implements OutboundSyncPublisher
{
    public function publish(string $domain, array $payload): void
    {
        Log::info('Outbound sync skipped; null publisher in use.', [
            'domain' => $domain,
            'payload' => $payload,
        ]);
    }
}
