<?php

namespace App\Providers;

use App\Actions\Integrations\DeliverIntegrationOutboxEntryStubAction;
use App\Contracts\DeliversIntegrationOutboxEntry;
use App\Contracts\OutboundSyncPublisher;
use App\Services\Integrations\WipoConnectHttpDeliverer;
use App\Services\Sync\NullOutboundSyncPublisher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OutboundSyncPublisher::class, NullOutboundSyncPublisher::class);

        $this->app->bind(DeliversIntegrationOutboxEntry::class, function ($app) {
            return match (config('integrations.wipo_connect.delivery')) {
                'http' => $app->make(WipoConnectHttpDeliverer::class),
                default => $app->make(DeliverIntegrationOutboxEntryStubAction::class),
            };
        });
    }

    public function boot(): void
    {
        $publicStorage = public_path('storage');
        $storageTarget = storage_path('app/public');

        if (! File::exists($publicStorage) && File::isDirectory($storageTarget)) {
            try {
                File::link($storageTarget, $publicStorage);
            } catch (\Throwable) {
                // Ignore link creation failures and allow existing URL normalization fallbacks to continue working.
            }
        }
    }
}
