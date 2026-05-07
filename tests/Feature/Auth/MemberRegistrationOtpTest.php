<?php

use App\Mail\Members\MemberWelcomeMailable;
use App\Models\Association;
use App\Models\SecurityChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    ensureRole('member');
});

it('registers a member, creates a draft application, and starts an email OTP challenge', function () {
    $association = Association::factory()->create();

    $response = $this->postJson('/api/v1/auth/register-member', [
        'first_name' => 'Ada',
        'last_name' => 'Author',
        'email' => 'ada.author@example.com',
        'phone' => '+2348012345678',
        'nationality' => 'Nigerian',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'applicant_type' => 'author',
        'association_id' => $association->id,
        'accepted_terms' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'ada.author@example.com')
        ->assertJsonPath('data.member_application.applicant_type', 'author');

    $this->assertDatabaseHas('users', [
        'email' => 'ada.author@example.com',
        'account_type' => 'member',
        'nationality' => 'Nigerian',
    ]);

    $user = User::where('email', 'ada.author@example.com')->firstOrFail();

    expect($user->email_verified_at)->toBeNull();
    expect($user->hasRole('member'))->toBeTrue();

    $this->assertDatabaseHas('member_applications', [
        'user_id' => $user->id,
        'association_id' => $association->id,
        'applicant_type' => 'author',
        'application_status' => 'draft',
    ]);

    $this->assertDatabaseHas('security_challenges', [
        'user_id' => $user->id,
        'purpose' => 'member_registration_otp',
        'consumed_at' => null,
    ]);
});

it('verifies a member registration OTP and returns an authenticated session token', function () {
    Mail::fake();
    $user = User::factory()->unverified()->create([
        'email' => 'verify.me@example.com',
        'account_type' => 'member',
    ]);
    $user->assignRole('member');

    SecurityChallenge::factory()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'purpose' => 'member_registration_otp',
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
        'consumed_at' => null,
    ]);

    $response = $this->postJson('/api/v1/auth/member-registration/verify-otp', [
        'email' => $user->email,
        'code' => '123456',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure(['data' => ['token', 'user']]);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
    expect(SecurityChallenge::where('user_id', $user->id)->first()->consumed_at)->not->toBeNull();
    Mail::assertQueued(MemberWelcomeMailable::class, 1);
});

it('rejects an invalid member registration OTP', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'wrong.code@example.com',
        'account_type' => 'member',
    ]);
    $user->assignRole('member');

    $challenge = SecurityChallenge::factory()->create([
        'user_id' => $user->id,
        'email' => $user->email,
        'purpose' => 'member_registration_otp',
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
        'consumed_at' => null,
        'attempts' => 0,
    ]);

    $response = $this->postJson('/api/v1/auth/member-registration/verify-otp', [
        'email' => $user->email,
        'code' => '000000',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);

    expect($challenge->fresh()->attempts)->toBe(1);
    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('resends OTP for an unverified member', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'resend@example.com',
        'account_type' => 'member',
    ]);
    $user->assignRole('member');

    $response = $this->postJson('/api/v1/auth/member-registration/resend-otp', [
        'email' => $user->email,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['expires_at']]);

    $this->assertDatabaseHas('security_challenges', [
        'user_id' => $user->id,
        'purpose' => 'member_registration_otp',
    ]);
});
