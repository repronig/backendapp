<?php

use App\Models\Association;
use App\Rules\RecaptchaToken;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    ensureRole('member');
    config([
        'app.frontend_url' => 'https://app.repronig.org',
        'cors.allowed_origins' => ['https://app.repronig.org'],
    ]);
});

it('rejects web member registration when recaptcha is enabled and google rejects the token', function () {
    config(['services.recaptcha.secret' => 'test-secret']);

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false], 200),
    ]);

    $association = Association::factory()->create();

    $response = $this->withHeader('Origin', 'https://app.repronig.org')
        ->postJson('/api/v1/auth/register-member', [
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

it('rejects web member registration when recaptcha is enabled and token is missing', function () {
    config(['services.recaptcha.secret' => 'test-secret']);

    $association = Association::factory()->create();

    $response = $this->withHeader(RecaptchaToken::WEB_CLIENT_HEADER, RecaptchaToken::WEB_CLIENT_VALUE)
        ->postJson('/api/v1/auth/register-member', [
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

it('allows native-style member registration without recaptcha when secret is configured', function () {
    config(['services.recaptcha.secret' => 'test-secret']);

    Http::fake();

    $association = Association::factory()->create();

    $response = $this->postJson('/api/v1/auth/register-member', [
        'first_name' => 'Mobile',
        'last_name' => 'User',
        'email' => 'mobile.no.captcha@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'applicant_type' => 'author',
        'association_id' => $association->id,
        'accepted_terms' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'mobile.no.captcha@example.com');

    Http::assertNothingSent();
});
