<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    ensureRole('admin');
    ensureRole('super_admin');
});

it('includes active users with admin account_type even without spatie role pivot', function () {
    $admin = User::factory()->create([
        'account_type' => 'admin',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    expect(User::adminAlertRecipients()->pluck('id'))->toContain($admin->id);
});

it('includes active users who only have the admin spatie role', function () {
    $user = User::factory()->create([
        'account_type' => 'institution_user',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);
    $user->assignRole('admin');

    expect(User::adminAlertRecipients()->pluck('id'))->toContain($user->id);
});

it('excludes inactive admins by account_type', function () {
    $admin = User::factory()->create([
        'account_type' => 'admin',
        'status' => 'inactive',
        'email_verified_at' => now(),
    ]);

    expect(User::adminAlertRecipients()->pluck('id'))->not->toContain($admin->id);
});
