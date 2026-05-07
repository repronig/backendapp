<?php

use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use App\Models\ExternalIntegration;
use App\Models\IntegrationOutboxEntry;
use App\Models\IntegrationWebhookEvent;
use App\Models\Work;
use Illuminate\Support\Facades\Config;

it('accepts a valid WIPO Connect inbound webhook and updates the outbox row', function () {
    ExternalIntegration::factory()->create([
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
        'webhook_secret' => 'whsec_test_inbound',
    ]);

    $work = Work::factory()->create();

    $entry = IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'subject_type' => $work->getMorphClass(),
        'subject_id' => $work->id,
        'status' => IntegrationSyncStatus::Processing,
    ]);

    $this->postJson('/api/v1/webhooks/wipo-connect', [
        'idempotency_key' => 'evt-'.uniqid(),
        'environment' => IntegrationEnvironment::Sandbox->value,
        'outbox_id' => $entry->id,
        'event' => 'succeeded',
    ], [
        'X-Repronig-Webhook-Secret' => 'whsec_test_inbound',
    ])->assertOk()
        ->assertJsonPath('data.outbox_updated', true);

    expect($entry->fresh()->status)->toBe(IntegrationSyncStatus::Succeeded)
        ->and($entry->fresh()->processed_at)->not->toBeNull();
});

it('returns duplicate for repeated idempotency keys', function () {
    ExternalIntegration::factory()->create([
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
        'webhook_secret' => 'same-secret',
    ]);

    $key = 'evt-dup-'.uniqid();

    $this->postJson('/api/v1/webhooks/wipo-connect', [
        'idempotency_key' => $key,
        'environment' => IntegrationEnvironment::Sandbox->value,
        'event' => 'acknowledged',
    ], [
        'X-Repronig-Webhook-Secret' => 'same-secret',
    ])->assertOk();

    $this->postJson('/api/v1/webhooks/wipo-connect', [
        'idempotency_key' => $key,
        'environment' => IntegrationEnvironment::Sandbox->value,
        'event' => 'acknowledged',
    ], [
        'X-Repronig-Webhook-Secret' => 'same-secret',
    ])->assertOk()
        ->assertJsonPath('data.duplicate', true);

    expect(IntegrationWebhookEvent::query()->where('idempotency_key', $key)->count())->toBe(1);
});

it('rejects inbound webhooks without a valid secret', function () {
    ExternalIntegration::factory()->create([
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
        'webhook_secret' => 'expected',
    ]);

    $this->postJson('/api/v1/webhooks/wipo-connect', [
        'idempotency_key' => 'evt-'.uniqid(),
        'environment' => IntegrationEnvironment::Sandbox->value,
        'event' => 'acknowledged',
    ], [
        'X-Repronig-Webhook-Secret' => 'wrong',
    ])->assertStatus(401);
});

it('accepts inbound webhooks when HMAC mode matches the raw body', function () {
    Config::set('integrations.wipo_connect.webhook_require_hmac', true);

    ExternalIntegration::factory()->create([
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
        'webhook_secret' => 'whsec_hmac_body',
    ]);

    $payload = [
        'idempotency_key' => 'evt-hmac-'.uniqid(),
        'environment' => IntegrationEnvironment::Sandbox->value,
        'event' => 'acknowledged',
    ];
    $raw = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $raw, 'whsec_hmac_body');

    $this->call('POST', '/api/v1/webhooks/wipo-connect', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_REPRONIG_SIGNATURE' => $signature,
    ], $raw)->assertOk();
});

it('rejects inbound webhooks when HMAC mode is enabled and the signature is wrong', function () {
    Config::set('integrations.wipo_connect.webhook_require_hmac', true);

    ExternalIntegration::factory()->create([
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
        'webhook_secret' => 'whsec_hmac_body',
    ]);

    $payload = [
        'idempotency_key' => 'evt-hmac-bad-'.uniqid(),
        'environment' => IntegrationEnvironment::Sandbox->value,
        'event' => 'acknowledged',
    ];
    $raw = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/v1/webhooks/wipo-connect', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_REPRONIG_SIGNATURE' => 'deadbeef',
    ], $raw)->assertStatus(401);
});

it('rejects inbound webhooks from IPs not on the allowlist', function () {
    Config::set('integrations.wipo_connect.webhook_allowed_ips', ['203.0.113.50']);

    ExternalIntegration::factory()->create([
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
        'webhook_secret' => 'whsec_allow',
    ]);

    $this->postJson('/api/v1/webhooks/wipo-connect', [
        'idempotency_key' => 'evt-ip-'.uniqid(),
        'environment' => IntegrationEnvironment::Sandbox->value,
        'event' => 'acknowledged',
    ], [
        'X-Repronig-Webhook-Secret' => 'whsec_allow',
    ])->assertStatus(403);
});
