<?php

use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use App\Models\ExternalIntegration;
use App\Models\Institution;
use App\Models\IntegrationOutboxEntry;
use App\Models\Licence;
use App\Models\Member;
use App\Models\Work;

beforeEach(function () {
    ensureRole('admin');
    ensureRole('super_admin');
    ensureRole('member');
});

it('allows admin to list WIPO outbox rows for a work', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    ExternalIntegration::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
    ]);

    $work = Work::factory()->create();

    IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'subject_type' => $work->getMorphClass(),
        'subject_id' => $work->id,
        'status' => IntegrationSyncStatus::Pending,
    ]);

    $this->getJson("/api/v1/admin/works/{$work->id}/wipo-connect/outbox")
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('allows admin to enqueue WIPO outbox for a work after security confirmation', function () {
    $user = actingAsApiUser('admin', ['account_type' => 'admin']);
    $user->forceFill(['last_security_confirmation_at' => now()])->save();

    ExternalIntegration::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
    ]);

    $work = Work::factory()->create();

    $this->postJson("/api/v1/admin/works/{$work->id}/wipo-connect/outbox", [])
        ->assertCreated()
        ->assertJsonPath('data.status', IntegrationSyncStatus::Pending->value);
});

it('denies admin WIPO outbox routes to members', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $work = Work::factory()->create();

    $this->getJson("/api/v1/admin/works/{$work->id}/wipo-connect/outbox")
        ->assertForbidden();
});

it('allows admin to list WIPO outbox rows for an institution', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    ExternalIntegration::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
    ]);

    $institution = Institution::factory()->create();

    IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'subject_type' => $institution->getMorphClass(),
        'subject_id' => $institution->id,
        'status' => IntegrationSyncStatus::Pending,
    ]);

    $this->getJson("/api/v1/admin/institutions/{$institution->id}/wipo-connect/outbox")
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('allows admin to list WIPO outbox rows for a member', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    ExternalIntegration::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
    ]);

    $member = Member::factory()->create();

    IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'subject_type' => $member->getMorphClass(),
        'subject_id' => $member->id,
        'status' => IntegrationSyncStatus::Pending,
    ]);

    $this->getJson("/api/v1/admin/members/{$member->id}/wipo-connect/outbox")
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('allows admin to list WIPO outbox rows for a licence', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    ExternalIntegration::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
    ]);

    $licence = Licence::factory()->create();

    IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'subject_type' => $licence->getMorphClass(),
        'subject_id' => $licence->id,
        'status' => IntegrationSyncStatus::Pending,
    ]);

    $this->getJson("/api/v1/admin/licences/{$licence->id}/wipo-connect/outbox")
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});
