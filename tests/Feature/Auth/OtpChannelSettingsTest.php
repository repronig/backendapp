<?php

use App\Models\Setting;
use App\Services\Sms\SmsOtpService;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;

beforeEach(function () {
    ensureRole('member');
});

it('sends otp via email only when sms channel is disabled in platform settings', function () {
    Mail::fake();
    Setting::query()->updateOrCreate(
        ['group' => 'general', 'key' => 'security'],
        ['value' => ['otp_email_enabled' => true, 'otp_sms_enabled' => false]]
    );

    $association = associationForApplicantType('author');

    $this->postJson('/api/v1/auth/register-member', [
        'first_name' => 'Email',
        'last_name' => 'Only',
        'email' => 'email.only.otp@example.com',
        'phone' => '+2348012345601',
        'nationality' => 'Nigerian',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'applicant_type' => 'author',
        'association_id' => $association->id,
        'accepted_terms' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.otp_delivery.email_sent', true)
        ->assertJsonPath('data.otp_delivery.sms_sent', false);
});

it('sends otp via sms only when email channel is disabled in platform settings', function () {
    Mail::fake();
    Setting::query()->updateOrCreate(
        ['group' => 'general', 'key' => 'security'],
        ['value' => ['otp_email_enabled' => false, 'otp_sms_enabled' => true]]
    );

    $this->mock(SmsOtpService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('sendOtp')->once()->andReturn(true);
    });

    $association = associationForApplicantType('author');

    $this->postJson('/api/v1/auth/register-member', [
        'first_name' => 'Sms',
        'last_name' => 'Only',
        'email' => 'sms.only.otp@example.com',
        'phone' => '+2348012345602',
        'nationality' => 'Nigerian',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'applicant_type' => 'author',
        'association_id' => $association->id,
        'accepted_terms' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.otp_delivery.email_sent', false)
        ->assertJsonPath('data.otp_delivery.sms_sent', true);

    Mail::assertNothingQueued();
});
