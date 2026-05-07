<?php

use App\Models\SecurityChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    ensureRole('member');
});

it('returns a consistent 401 auth error envelope for invalid login credentials', function () {
    User::factory()->create([
        'email' => 'contract.login@example.com',
        'password' => Hash::make('Password123!'),
        'account_type' => 'member',
        'email_verified_at' => now(),
    ])->assignRole('member');

    $this->postJson('/api/v1/auth/login', [
        'email' => 'contract.login@example.com',
        'password' => 'WrongPassword123!',
    ])->assertUnauthorized()
        ->assertJsonStructure(['message'])
        ->assertJsonMissingPath('data');
});

it('returns a consistent 401 auth error envelope for invalid two-factor verification', function () {
    $user = User::factory()->create([
        'email' => 'contract.2fa@example.com',
        'account_type' => 'member',
        'email_verified_at' => now(),
    ]);
    $user->assignRole('member');

    $challenge = SecurityChallenge::factory()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'purpose' => 'login',
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
        'consumed_at' => null,
    ]);

    $this->postJson('/api/v1/auth/two-factor/verify', [
        'challenge_id' => $challenge->id,
        'code' => '000000',
    ])->assertUnauthorized()
        ->assertJsonStructure(['message'])
        ->assertJsonMissingPath('data');
});

it('keeps notification list envelope stable for authenticated clients', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->getJson('/api/v1/me/notifications')
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});

it('supports category filter on notifications endpoint', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);

    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\System\\AccountSecuritySystemNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode([
            'type' => 'account_security',
            'title' => 'Suspicious login blocked',
            'message' => 'A suspicious login attempt was blocked.',
            'severity' => 'warning',
            'channel' => 'system',
            'action_url' => '/member/settings',
            'category' => 'security',
        ], JSON_THROW_ON_ERROR),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->getJson('/api/v1/me/notifications?category=security')
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});

it('normalizes unsupported public states per_page values to the endpoint default', function () {
    $this->getJson('/api/v1/locations/states?per_page=15')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100)
        ->assertJsonStructure([
            'message',
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});
