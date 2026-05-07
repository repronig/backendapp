<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalIntegrationResource extends JsonResource
{
    /**
     * @var list<string>
     */
    private const CONFIG_PUBLIC_KEYS = [
        'api_base_url',
        'realm',
        'tenant_id',
        'sync_path',
        'sync_http_method',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'environment' => $this->environment,
            'is_enabled' => (bool) $this->is_enabled,
            'config' => $this->publicConfig(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publicConfig(): array
    {
        $config = is_array($this->config) ? $this->config : [];

        return array_intersect_key($config, array_flip(self::CONFIG_PUBLIC_KEYS));
    }
}
