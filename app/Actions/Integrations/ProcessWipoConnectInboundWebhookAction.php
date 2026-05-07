<?php

namespace App\Actions\Integrations;

use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use App\Models\ExternalIntegration;
use App\Models\IntegrationOutboxEntry;
use App\Models\IntegrationWebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProcessWipoConnectInboundWebhookAction
{
    /**
     * @param  array{idempotency_key: string, environment: string, event: string, outbox_id?: int, message?: string|null}  $payload
     * @return array{duplicate: bool, outbox_updated: bool}
     */
    public function execute(
        array $payload,
        ?string $plainWebhookSecretHeader,
        ?string $rawRequestBody = null,
        ?string $signatureHeader = null,
    ): array {
        $environment = IntegrationEnvironment::from($payload['environment']);

        $integration = ExternalIntegration::query()
            ->where('provider', IntegrationProvider::WipoConnect)
            ->where('environment', $environment)
            ->where('is_enabled', true)
            ->first();

        if ($integration === null || $integration->webhook_secret === null || $integration->webhook_secret === '') {
            throw new AccessDeniedHttpException('Unknown integration or webhook not configured.');
        }

        if (config('integrations.wipo_connect.webhook_require_hmac')) {
            if ($rawRequestBody === null || $signatureHeader === null || $signatureHeader === '') {
                throw new AccessDeniedHttpException('X-Repronig-Signature (HMAC-SHA256 of the raw request body) is required when WIPO_CONNECT_WEBHOOK_REQUIRE_HMAC is enabled.');
            }

            $expected = hash_hmac('sha256', $rawRequestBody, (string) $integration->webhook_secret);

            if (! hash_equals($expected, $signatureHeader)) {
                throw new AccessDeniedHttpException('Invalid webhook signature.');
            }
        } else {
            if ($plainWebhookSecretHeader === null || $plainWebhookSecretHeader === '') {
                throw new AccessDeniedHttpException('Missing X-Repronig-Webhook-Secret header.');
            }

            if (! hash_equals((string) $integration->webhook_secret, $plainWebhookSecretHeader)) {
                throw new AccessDeniedHttpException('Invalid webhook secret.');
            }
        }

        return DB::transaction(function () use ($payload): array {
            try {
                IntegrationWebhookEvent::query()->create([
                    'provider' => IntegrationProvider::WipoConnect,
                    'idempotency_key' => $payload['idempotency_key'],
                    'payload' => $payload,
                ]);
            } catch (QueryException $e) {
                if ($this->isUniqueConstraintViolation($e)) {
                    return ['duplicate' => true, 'outbox_updated' => false];
                }

                throw $e;
            }

            $outboxUpdated = false;

            if (isset($payload['outbox_id'])) {
                /** @var IntegrationOutboxEntry|null $entry */
                $entry = IntegrationOutboxEntry::query()
                    ->whereKey((int) $payload['outbox_id'])
                    ->where('provider', IntegrationProvider::WipoConnect)
                    ->lockForUpdate()
                    ->first();

                if ($entry !== null) {
                    $this->applyEventToOutbox($entry, $payload['event'], $payload['message'] ?? null);
                    $outboxUpdated = true;
                }
            }

            return ['duplicate' => false, 'outbox_updated' => $outboxUpdated];
        });
    }

    private function applyEventToOutbox(IntegrationOutboxEntry $entry, string $event, ?string $message): void
    {
        if ($event === 'succeeded') {
            $entry->update([
                'status' => IntegrationSyncStatus::Succeeded,
                'processed_at' => now(),
                'last_error' => null,
            ]);

            return;
        }

        if ($event === 'failed') {
            $entry->update([
                'status' => IntegrationSyncStatus::Failed,
                'last_error' => $message ?? 'Inbound webhook reported failure.',
            ]);

            return;
        }

        // acknowledged — record only; optional light touch on payload
        $payload = is_array($entry->payload) ? $entry->payload : [];
        $payload['wipo_acknowledged_at'] = now()->toIso8601String();
        $entry->update(['payload' => $payload]);
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? '';

        return $sqlState === '23505' || str_contains(strtolower($e->getMessage()), 'unique');
    }
}
