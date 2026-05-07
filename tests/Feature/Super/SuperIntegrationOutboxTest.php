<?php

use App\Actions\Integrations\DispatchIntegrationSyncAction;
use App\Actions\Integrations\ProcessPendingIntegrationOutboxAction;
use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use App\Jobs\Integrations\ProcessIntegrationOutboxJob;
use App\Models\ExternalIntegration;
use App\Models\IntegrationOutboxEntry;
use App\Models\Work;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    ensureRole('super_admin');
    ensureRole('admin');
});

it('denies integration routes to non super admins', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/super/integrations')->assertForbidden();
});

it('allows super admin to read integration outbox summary', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'status' => IntegrationSyncStatus::Pending,
    ]);

    $this->getJson('/api/v1/super/integrations/outbox/summary')
        ->assertOk()
        ->assertJsonPath('data.pending_total', 1);
});

it('allows super admin to upsert WIPO Connect and enqueue then process outbox entries', function () {
    $user = actingAsApiUser('super_admin', ['account_type' => 'super_admin']);
    $user->forceFill(['last_security_confirmation_at' => now()])->save();

    $this->getJson('/api/v1/super/integrations')->assertOk()
        ->assertJsonPath('meta.total', 0);

    $upsert = $this->putJson('/api/v1/super/integrations/wipo_connect/sandbox', [
        'is_enabled' => true,
        'config' => ['api_base_url' => 'https://wipo.test', 'secret_key' => 'hidden'],
    ])->assertOk()
        ->assertJsonPath('data.is_enabled', true)
        ->assertJsonPath('data.config.api_base_url', 'https://wipo.test');

    expect($upsert->json('data.config'))->not->toHaveKey('secret_key');

    $work = Work::factory()->create();

    $this->postJson("/api/v1/super/works/{$work->id}/wipo-connect/outbox", [
        'payload' => ['note' => 'phase_d'],
    ])->assertCreated()
        ->assertJsonPath('data.status', IntegrationSyncStatus::Pending->value);

    $this->postJson("/api/v1/super/works/{$work->id}/wipo-connect/outbox", [])
        ->assertOk()
        ->assertJsonPath('message', 'A pending outbox entry already exists for this work and operation.');

    $processed = app(ProcessPendingIntegrationOutboxAction::class)->execute(10);
    expect($processed)->toBe(1);

    $entry = IntegrationOutboxEntry::query()->where('subject_id', $work->id)->first();
    expect($entry->status)->toBe(IntegrationSyncStatus::Succeeded)
        ->and($entry->processed_at)->not->toBeNull();
});

it('returns 422 when enqueueing WIPO Connect outbox without an enabled integration', function () {
    $user = actingAsApiUser('super_admin', ['account_type' => 'super_admin']);
    $user->forceFill(['last_security_confirmation_at' => now()])->save();

    ExternalIntegration::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => false,
    ]);

    $work = Work::factory()->create();

    $this->postJson("/api/v1/super/works/{$work->id}/wipo-connect/outbox", [])
        ->assertStatus(422);
});

it('requires recent security confirmation to enqueue WIPO Connect outbox', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    ExternalIntegration::factory()->create([
        'is_enabled' => true,
    ]);

    $work = Work::factory()->create();

    $this->postJson("/api/v1/super/works/{$work->id}/wipo-connect/outbox", [])
        ->assertStatus(423);
});

it('allows super admin to requeue a failed outbox entry after security confirmation', function () {
    $user = actingAsApiUser('super_admin', ['account_type' => 'super_admin']);
    $user->forceFill(['last_security_confirmation_at' => now()])->save();

    $entry = IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'status' => IntegrationSyncStatus::Failed,
        'attempts' => 3,
        'last_error' => 'upstream timeout',
    ]);

    $this->postJson("/api/v1/super/integrations/outbox/{$entry->id}/requeue", [])
        ->assertOk()
        ->assertJsonPath('data.status', IntegrationSyncStatus::Pending->value);

    $entry->refresh();
    expect($entry->attempts)->toBe(0)
        ->and($entry->last_error)->toBeNull()
        ->and($entry->scheduled_at)->toBeNull();
});

it('rejects requeue when the outbox entry is not failed', function () {
    $user = actingAsApiUser('super_admin', ['account_type' => 'super_admin']);
    $user->forceFill(['last_security_confirmation_at' => now()])->save();

    $entry = IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'status' => IntegrationSyncStatus::Pending,
    ]);

    $this->postJson("/api/v1/super/integrations/outbox/{$entry->id}/requeue", [])
        ->assertStatus(422);
});

it('dispatches integration outbox job when provider sync is requested and integration is enabled', function () {
    Queue::fake();

    ExternalIntegration::factory()->create([
        'is_enabled' => true,
    ]);

    $dispatch = app(DispatchIntegrationSyncAction::class);
    expect($dispatch->execute(IntegrationProvider::WipoConnect))->toBeTrue();

    Queue::assertPushed(ProcessIntegrationOutboxJob::class);
});
