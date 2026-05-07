<?php

use App\Models\User;

beforeEach(function () {
    ensureRole('member');
    ensureRole('admin');
    ensureRole('super_admin');
});

it('rejects PATCH me when phone belongs to another user', function () {
    actingAsApprovedMember();

    $other = User::factory()->create([
        'phone' => '+2348099910001',
        'account_type' => 'member',
    ]);

    $this->patchJson('/api/v1/me', [
        'phone' => $other->phone,
    ])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['phone']]);
});

it('allows PATCH me when keeping the same phone number', function () {
    [$user] = actingAsApprovedMember();
    $user->update(['phone' => '+2348099910003']);

    $this->patchJson('/api/v1/me', [
        'phone' => '+2348099910003',
    ])
        ->assertOk()
        ->assertJsonPath('data.phone', '+2348099910003');
});

it('rejects super-managed user create when phone is already taken', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    User::factory()->create([
        'phone' => '+2348099910004',
    ]);

    $this->postJson('/api/v1/super/users', [
        'first_name' => 'New',
        'last_name' => 'Admin',
        'email' => 'new.unique.admin@example.test',
        'phone' => '+2348099910004',
        'password' => 'Password123!',
        'account_type' => 'admin',
        'roles' => ['admin'],
    ])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['phone']]);
});

it('allows super-managed user update when phone unchanged', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $target = User::factory()->create([
        'account_type' => 'admin',
        'phone' => '+2348099910005',
        'email' => 'managed.admin@example.test',
    ]);
    $target->assignRole('admin');

    $this->patchJson("/api/v1/super/users/{$target->id}", [
        'first_name' => 'UpdatedName',
        'phone' => '+2348099910005',
    ])
        ->assertOk()
        ->assertJsonPath('data.phone', '+2348099910005');
});
