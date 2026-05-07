<?php

use App\Actions\Compliance\BuildInstitutionComplianceSnapshotAction;
use App\Actions\Integrations\DispatchIntegrationSyncAction;
use App\Actions\Integrations\EnqueueWipoConnectPayloadAction;
use App\Enums\ComplianceOverallStatus;
use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use App\Models\AutomationDefinition;
use App\Models\ExternalIntegration;
use App\Models\Institution;
use App\Models\IntegrationOutboxEntry;
use App\Support\AutomationRegistry;
use Database\Seeders\AutomationDefinitionSeeder;
use Illuminate\Support\Facades\Queue;

it('persists external integration and outbox rows with enum casts', function () {
    $integration = ExternalIntegration::query()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'environment' => IntegrationEnvironment::Sandbox,
        'config' => ['endpoint' => 'https://example.test'],
        'is_enabled' => false,
        'webhook_secret' => 'whsec_test',
    ]);

    expect($integration->fresh()->provider)->toBe(IntegrationProvider::WipoConnect);

    $entry = IntegrationOutboxEntry::query()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'operation' => 'publish_work',
        'payload' => ['reference' => 'abc'],
        'status' => IntegrationSyncStatus::Pending,
        'attempts' => 0,
    ]);

    expect($entry->fresh()->status)->toBe(IntegrationSyncStatus::Pending);
});

it('runs stub integration and compliance actions', function () {
    Queue::fake();

    $institution = Institution::factory()->create();

    $dispatch = app(DispatchIntegrationSyncAction::class);
    expect($dispatch->execute(IntegrationProvider::WipoConnect))->toBeFalse();

    ExternalIntegration::query()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'environment' => IntegrationEnvironment::Sandbox,
        'config' => [],
        'is_enabled' => true,
        'webhook_secret' => null,
    ]);

    expect($dispatch->execute(IntegrationProvider::WipoConnect))->toBeTrue();

    $enqueued = app(EnqueueWipoConnectPayloadAction::class)->execute($institution, ['sample' => true]);
    expect($enqueued)->not->toBeNull()
        ->and($enqueued['created'])->toBeTrue()
        ->and(IntegrationOutboxEntry::query()->where('subject_id', $institution->id)->count())->toBe(1);

    $snapshot = app(BuildInstitutionComplianceSnapshotAction::class)->execute($institution);
    expect($snapshot['overall_status'])->toBe(ComplianceOverallStatus::Ok->value)
        ->and($snapshot['institution_id'])->toBe($institution->id);
});

it('seeds disabled automation definitions and exposes registry keys', function () {
    $this->seed(AutomationDefinitionSeeder::class);

    expect(AutomationDefinition::query()->where('key', 'invoice_reminder')->value('is_enabled'))->toBeFalse()
        ->and(AutomationRegistry::handlers())->toHaveKeys(['invoice_reminder', 'declaration_follow_up']);
});
