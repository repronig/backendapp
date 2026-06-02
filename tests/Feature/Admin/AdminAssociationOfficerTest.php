<?php

use App\Models\Association;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    ensureRole('admin');
    ensureRole('association_officer');
});

it('lists association officers for an association', function () {
    $admin = actingAsApiUser('admin', ['account_type' => 'admin']);
    $association = Association::factory()->create();

    [$officer] = actingAsAssociationOfficer();
    $officer->associations()->sync([$association->id => ['designation_title' => 'Secretary', 'is_active' => true]]);

    Sanctum::actingAs($admin);

    $this->getJson("/api/v1/admin/associations/{$association->id}/officers")
        ->assertOk()
        ->assertJsonPath('message', 'Association officers retrieved successfully.')
        ->assertJsonPath('data.0.id', $officer->id);
});

it('updates association officer email and password', function () {
    $admin = actingAsApiUser('admin', [
        'account_type' => 'admin',
        'last_security_confirmation_at' => now(),
    ]);
    $association = Association::factory()->create();

    [$officer] = actingAsAssociationOfficer();
    $officer->associations()->sync([$association->id => ['designation_title' => 'Secretary', 'is_active' => true]]);

    Sanctum::actingAs($admin);

    $this->patchJson("/api/v1/admin/associations/{$association->id}/officers/{$officer->id}", [
        'email' => 'officer.updated@example.com',
        'password' => 'new-password-1',
    ])
        ->assertOk()
        ->assertJsonPath('data.email', 'officer.updated@example.com');

    $officer->refresh();
    expect($officer->email)->toBe('officer.updated@example.com');
    expect(Hash::check('new-password-1', $officer->password))->toBeTrue();
});

it('rejects updates for users who are not officers of the association', function () {
    $admin = actingAsApiUser('admin', [
        'account_type' => 'admin',
        'last_security_confirmation_at' => now(),
    ]);
    $association = Association::factory()->create();
    $otherAssociation = Association::factory()->create();

    [$officer] = actingAsAssociationOfficer();
    $officer->associations()->sync([$otherAssociation->id => ['designation_title' => 'Secretary', 'is_active' => true]]);

    Sanctum::actingAs($admin);

    $this->patchJson("/api/v1/admin/associations/{$association->id}/officers/{$officer->id}", [
        'email' => 'should-not-update@example.com',
    ])->assertNotFound();
});

it('denies association officer management to members', function () {
    actingAsApiUser('member', ['account_type' => 'member']);
    $association = Association::factory()->create();

    $this->getJson("/api/v1/admin/associations/{$association->id}/officers")->assertForbidden();
});
