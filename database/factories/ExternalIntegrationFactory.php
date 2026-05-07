<?php

namespace Database\Factories;

use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Models\ExternalIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalIntegration>
 */
class ExternalIntegrationFactory extends Factory
{
    protected $model = ExternalIntegration::class;

    public function definition(): array
    {
        return [
            'provider' => IntegrationProvider::WipoConnect,
            'environment' => IntegrationEnvironment::Sandbox,
            'config' => [],
            'is_enabled' => false,
            'webhook_secret' => null,
        ];
    }
}
