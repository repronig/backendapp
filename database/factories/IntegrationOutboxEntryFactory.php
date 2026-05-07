<?php

namespace Database\Factories;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use App\Models\IntegrationOutboxEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationOutboxEntry>
 */
class IntegrationOutboxEntryFactory extends Factory
{
    protected $model = IntegrationOutboxEntry::class;

    public function definition(): array
    {
        return [
            'provider' => IntegrationProvider::WipoConnect,
            'subject_type' => null,
            'subject_id' => null,
            'operation' => 'sync_work',
            'payload' => [],
            'status' => IntegrationSyncStatus::Pending,
            'attempts' => 0,
            'scheduled_at' => null,
            'processed_at' => null,
        ];
    }
}
