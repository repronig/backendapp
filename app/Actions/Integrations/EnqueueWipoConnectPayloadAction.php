<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use App\Models\ExternalIntegration;
use App\Models\IntegrationOutboxEntry;
use Illuminate\Database\Eloquent\Model;

class EnqueueWipoConnectPayloadAction
{
    /**
     * Queue a WIPO Connect outbox row when an integration is enabled.
     *
     * @return array{entry: IntegrationOutboxEntry, created: bool}|null Null when no enabled integration matches.
     */
    public function execute(
        Model $subject,
        array $payload = [],
        string $operation = 'sync_work',
        ?IntegrationEnvironment $environment = null,
    ): ?array {
        $query = ExternalIntegration::query()
            ->where('provider', IntegrationProvider::WipoConnect)
            ->where('is_enabled', true);

        if ($environment !== null) {
            $query->where('environment', $environment);
        }

        $integration = $query
            ->orderByRaw('CASE WHEN environment = ? THEN 0 ELSE 1 END', [IntegrationEnvironment::Sandbox->value])
            ->first();

        if ($integration === null) {
            return null;
        }

        $subjectType = $subject->getMorphClass();
        $subjectId = $subject->getKey();

        $duplicate = IntegrationOutboxEntry::query()
            ->where('provider', IntegrationProvider::WipoConnect)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('operation', $operation)
            ->where('status', IntegrationSyncStatus::Pending)
            ->first();

        if ($duplicate !== null) {
            return ['entry' => $duplicate, 'created' => false];
        }

        $mergedPayload = array_merge(
            [
                'environment' => $integration->environment->value,
                'queued_at' => now()->toIso8601String(),
            ],
            $payload
        );

        $entry = IntegrationOutboxEntry::query()->create([
            'provider' => IntegrationProvider::WipoConnect,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'operation' => $operation,
            'payload' => $mergedPayload,
            'status' => IntegrationSyncStatus::Pending,
            'attempts' => 0,
            'scheduled_at' => null,
        ]);

        return ['entry' => $entry, 'created' => true];
    }
}
