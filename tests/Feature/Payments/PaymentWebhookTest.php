<?php

use App\Enums\LicencePaymentStatus;
use App\Mail\Payments\PaymentReceivedAdminMailable;
use App\Mail\Payments\PaymentReceivedMailable;
use App\Models\LicencePayment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    ensureRole('admin');
    ensureRole('super_admin');
});

it('accepts a valid Paystack webhook signature and marks a pending payment as paid', function () {
    config(['services.paystack.secret_key' => 'sk_test_webhook_secret']);
    Mail::fake();

    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email' => 'admin-payhook@example.test',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');

    $payment = LicencePayment::factory()->create([
        'gateway_name' => 'paystack',
        'payment_status' => LicencePaymentStatus::Pending->value,
        'payment_reference' => 'PAY-WEBHOOK-REF-001',
        'amount' => 5000,
        'amount_allocated' => 0,
        'balance_before' => 5000,
        'balance_after' => 5000,
    ]);

    $rawBody = '{"event":"charge.success","data":{"reference":"PAY-WEBHOOK-REF-001","status":"success","amount":500000}}';
    $signature = hash_hmac('sha512', $rawBody, 'sk_test_webhook_secret');

    $response = $this->call(
        'POST',
        '/api/v1/payments/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
        ],
        $rawBody
    );

    $response->assertOk()
        ->assertJsonPath('data.handled', true)
        ->assertJsonPath('data.payment_reference', 'PAY-WEBHOOK-REF-001');

    expect($payment->fresh()->payment_status)->toBe(LicencePaymentStatus::Paid->value)
        ->and($payment->fresh()->paid_at)->not->toBeNull();

    Mail::assertQueued(PaymentReceivedMailable::class, function (PaymentReceivedMailable $mailable): bool {
        $attachments = $mailable->attachments();

        return count($attachments) >= 1;
    });

    Mail::assertQueued(PaymentReceivedAdminMailable::class, 1);
});

it('rejects a Paystack webhook when the signature does not match', function () {
    config(['services.paystack.secret_key' => 'sk_test_webhook_secret']);

    LicencePayment::factory()->create([
        'gateway_name' => 'paystack',
        'payment_status' => LicencePaymentStatus::Pending->value,
        'payment_reference' => 'PAY-WEBHOOK-REF-002',
    ]);

    $rawBody = '{"event":"charge.success","data":{"reference":"PAY-WEBHOOK-REF-002","status":"success","amount":100}}';

    $response = $this->call(
        'POST',
        '/api/v1/payments/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => 'invalid-signature',
        ],
        $rawBody
    );

    $response->assertUnauthorized();
});
