<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Models\ExternalIntegration;
use Illuminate\Support\Arr;

class UpsertExternalIntegrationAction
{
    /**
     * @param  array{is_enabled?: bool, config?: array<string, mixed>|null, webhook_secret?: string|null}  $data
     */
    public function execute(
        IntegrationProvider $provider,
        IntegrationEnvironment $environment,
        array $data,
    ): ExternalIntegration {
        $payload = Arr::only($data, ['is_enabled', 'config', 'webhook_secret']);

        return ExternalIntegration::query()->updateOrCreate(
            [
                'provider' => $provider,
                'environment' => $environment,
            ],
            [
                'is_enabled' => (bool) ($payload['is_enabled'] ?? false),
                'config' => $payload['config'] ?? [],
                'webhook_secret' => $payload['webhook_secret'] ?? null,
            ]
        )->fresh();
    }
}
