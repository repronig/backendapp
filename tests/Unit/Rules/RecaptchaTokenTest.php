<?php

use App\Rules\RecaptchaToken;
use Illuminate\Http\Request;

beforeEach(function () {
    config([
        'app.frontend_url' => 'https://app.repronig.org',
        'cors.allowed_origins' => [
            'https://staging.repronig.org',
            'https://app.repronig.org',
        ],
    ]);
});

it('requires recaptcha for registration when enabled and X-Repronig-Client is web', function () {
    config(['services.recaptcha.secret' => 'test-secret']);

    $web = Request::create('/api/v1/auth/register-member', 'POST', server: [
        'HTTP_X_REPRONIG_CLIENT' => 'web',
    ]);

    expect(RecaptchaToken::requiredForRegistration($web))->toBeTrue();
});

it('requires recaptcha for registration when enabled and Origin matches the frontend', function () {
    config(['services.recaptcha.secret' => 'test-secret']);

    $web = Request::create('/api/v1/auth/register-member', 'POST', server: [
        'HTTP_ORIGIN' => 'https://app.repronig.org',
    ]);

    expect(RecaptchaToken::requiredForRegistration($web))->toBeTrue();
});

it('does not require recaptcha for native-style requests without web Origin or header', function () {
    config(['services.recaptcha.secret' => 'test-secret']);

    $mobile = Request::create('/api/v1/auth/register-member', 'POST');

    expect(RecaptchaToken::requiredForRegistration($mobile))->toBeFalse();
});

it('does not require recaptcha when secret is not configured', function () {
    config(['services.recaptcha.secret' => null]);

    $web = Request::create('/api/v1/auth/register-member', 'POST', server: [
        'HTTP_ORIGIN' => 'https://app.repronig.org',
    ]);

    expect(RecaptchaToken::requiredForRegistration($web))->toBeFalse();
});
