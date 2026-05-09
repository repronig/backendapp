<?php

use App\Mail\Institutions\InstitutionWelcomeMailable;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\SecurityChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    ensureRole('institution_user');
});

it('verifies an institution registration OTP and returns an authenticated session token', function () {
    Mail::fake();
    $user = User::factory()->unverified()->create([
        'email' => 'institution.verify@example.com',
        'account_type' => 'institution_user',
    ]);
    $user->assignRole('institution_user');
    $institution = Institution::factory()->create([
        'name' => 'Acme Institute',
        'email' => 'org@example.com',
    ]);
    InstitutionUser::factory()->primary()->create([
        'institution_id' => $institution->id,
        'user_id' => $user->id,
    ]);

    SecurityChallenge::factory()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'purpose' => 'institution_registration_otp',
        'code_hash' => Hash::make('654321'),
        'expires_at' => now()->addMinutes(10),
        'consumed_at' => null,
    ]);

    $response = $this->postJson('/api/v1/auth/institution-registration/verify-otp', [
        'email' => $user->email,
        'code' => '654321',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure(['data' => ['token', 'user']]);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
    expect(
        SecurityChallenge::query()
            ->where('user_id', $user->id)
            ->where('purpose', 'institution_registration_otp')
            ->latest('id')
            ->first()
            ?->consumed_at
    )->not->toBeNull();
    Mail::assertQueued(InstitutionWelcomeMailable::class, 1);
});

it('rejects an invalid institution registration OTP', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'institution.wrong@example.com',
        'account_type' => 'institution_user',
    ]);
    $user->assignRole('institution_user');

    SecurityChallenge::factory()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'purpose' => 'institution_registration_otp',
        'code_hash' => Hash::make('654321'),
        'expires_at' => now()->addMinutes(10),
        'consumed_at' => null,
        'attempts' => 0,
    ]);

    $response = $this->postJson('/api/v1/auth/institution-registration/verify-otp', [
        'email' => $user->email,
        'code' => '000000',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('resending verification for unverified institution user sends otp challenge instead of verify-email mail', function () {
    Mail::fake();
    $user = User::factory()->unverified()->create([
        'email' => 'institution.verify.resend@example.com',
        'account_type' => 'institution_user',
    ]);
    $user->assignRole('institution_user');
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/email/verification-notification')
        ->assertOk()
        ->assertJsonPath('message', 'A new email verification OTP has been sent to your email and SMS.')
        ->assertJsonStructure(['data' => ['expires_at', 'otp_delivery']]);

    $this->assertDatabaseHas('security_challenges', [
        'user_id' => $user->id,
        'purpose' => 'institution_registration_otp',
    ]);
});
