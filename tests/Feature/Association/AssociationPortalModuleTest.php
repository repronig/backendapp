<?php

use App\Models\MemberApplication;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    ensureRole('association_officer');
});

it('denies association routes to guests', function () {
    $this->getJson('/api/v1/association/dashboard')->assertUnauthorized();
    $this->getJson('/api/v1/association/profile')->assertUnauthorized();
});

it('returns association dashboard for a linked officer', function () {
    actingAsAssociationOfficer();

    $this->getJson('/api/v1/association/dashboard')
        ->assertOk()
        ->assertJsonPath('message', 'Association dashboard retrieved successfully.')
        ->assertJsonStructure(['data' => ['meta', 'association', 'stats', 'recent_applications', 'recent_activity']]);
});

it('returns association profile for a linked officer', function () {
    actingAsAssociationOfficer();

    $this->getJson('/api/v1/association/profile')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('lists member applications for the association', function () {
    [, $association] = actingAsAssociationOfficer();

    MemberApplication::factory()->create([
        'association_id' => $association->id,
        'application_status' => 'submitted',
    ]);

    $this->getJson('/api/v1/association/applications')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('includes member bank details when an association officer views an application', function () {
    [, $association] = actingAsAssociationOfficer();

    $application = MemberApplication::factory()->create([
        'association_id' => $association->id,
        'application_status' => 'submitted',
        'bank_name' => 'Zenith Bank',
        'bank_account_number' => '0123456789',
        'bank_account_owner_name' => 'Jane Applicant',
        'member_provided_id' => 'MEM-ID-001',
    ]);

    $this->getJson("/api/v1/association/applications/{$application->id}")
        ->assertOk()
        ->assertJsonPath('data.bank_name', 'Zenith Bank')
        ->assertJsonPath('data.bank_account_number', '0123456789')
        ->assertJsonPath('data.bank_account_owner_name', 'Jane Applicant')
        ->assertJsonPath('data.member_provided_id', 'MEM-ID-001');
});

it('copies member_provided_id onto the member record when admin approves after affiliation validation', function () {
    ensureRole('admin');
    ensureRole('super_admin');
    [, $association] = actingAsAssociationOfficer();

    $applicant = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $application = MemberApplication::factory()->create([
        'user_id' => $applicant->id,
        'association_id' => $association->id,
        'application_status' => 'submitted',
        'member_provided_id' => 'EXT-555',
    ]);

    $this->postJson("/api/v1/association/applications/{$application->id}/validate-affiliation", [])
        ->assertOk();

    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/member-applications/{$application->id}/approve", [])
        ->assertOk();

    $this->assertDatabaseHas('members', [
        'user_id' => $applicant->id,
        'member_provided_id' => 'EXT-555',
    ]);
});

it('allows association officer to reject affiliation on submitted application', function () {
    [, $association] = actingAsAssociationOfficer();

    $application = MemberApplication::factory()->create([
        'association_id' => $association->id,
        'application_status' => 'submitted',
        'notes' => null,
    ]);

    $response = $this->postJson("/api/v1/association/applications/{$application->id}/reject-affiliation", [
        'reason' => 'Affiliation could not be verified from submitted details.',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.application_status', 'submitted')
        ->assertJsonPath('data.affiliation_status', 'rejected');
});

it('returns forbidden when an association officer has no linked association', function () {
    ensureRole('association_officer');
    $user = User::factory()->create([
        'account_type' => 'association_officer',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $user->assignRole('association_officer');
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/association/dashboard')->assertForbidden();
});
