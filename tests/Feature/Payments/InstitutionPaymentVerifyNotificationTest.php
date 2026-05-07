<?php

use App\Enums\LicencePaymentStatus;
use App\Events\LicencePaymentReceived;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\Licence;
use App\Models\LicencePayment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    ensureRole('institution_user');
});

it('dispatches licence payment received after successful paystack verify', function () {
    Event::fake([LicencePaymentReceived::class]);
    config(['services.paystack.secret_key' => 'sk_test_verify']);

    [$user, $institution] = actingAsInstitutionUserWithInstitution();

    $declaration = InstitutionAnnualDeclaration::factory()->approved()->create([
        'institution_id' => $institution->id,
    ]);

    $licence = Licence::factory()->forDeclaration($declaration)->create();

    $payment = LicencePayment::factory()->create([
        'licence_id' => $licence->id,
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'gateway_name' => 'paystack',
        'payment_status' => LicencePaymentStatus::Pending->value,
        'payment_reference' => 'PAY-VERIFY-REF-001',
        'amount' => 5000,
        'amount_allocated' => 0,
        'balance_before' => 5000,
        'balance_after' => 5000,
        'currency' => 'NGN',
    ]);

    Http::fake([
        'api.paystack.co/*' => Http::response([
            'status' => true,
            'message' => 'Verification successful',
            'data' => [
                'status' => 'success',
                'amount' => 500000,
                'reference' => 'PAY-VERIFY-REF-001',
                'id' => 999001,
            ],
        ], 200),
    ]);

    $this->postJson("/api/v1/institution/payments/{$payment->id}/verify")
        ->assertOk()
        ->assertJsonPath('message', 'Payment verified successfully.');

    Event::assertDispatched(LicencePaymentReceived::class, function (LicencePaymentReceived $event): bool {
        return $event->payment->payment_status === LicencePaymentStatus::Paid->value;
    });
});
