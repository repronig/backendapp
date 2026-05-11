<?php

use App\Models\Association;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    ensureRole('member');
});

it('rejects member registration when recaptcha is enabled and google rejects the token', function () {
    config(['services.recaptcha.secret' => 'test-secret']);

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false], 200),
    ]);

    $association = Association::factory()->create();

    $response = $this->postJson('/api/v1/auth/register-member', [
        'first_name' => 'Ada',
        'last_name' => 'Author',
        'email' => 'ada.recaptcha@example.com',
        'phone' => '+2348012345999',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'applicant_type' => 'author',
        'association_id' => $association->id,
        'accepted_terms' => true,
        'recaptcha_token' => 'fake-client-token',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['recaptcha_token']);
});

it('rejects member registration when recaptcha is enabled and token is missing', function () {
    config(['services.recaptcha.secret' => 'test-secret']);

    $association = Association::factory()->create();

    $response = $this->postJson('/api/v1/auth/register-member', [
        'first_name' => 'Ada',
        'last_name' => 'Author',
        'email' => 'ada.no.token@example.com',
        'phone' => '+2348012345998',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'applicant_type' => 'author',
        'association_id' => $association->id,
        'accepted_terms' => true,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['recaptcha_token']);
});
