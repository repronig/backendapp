<?php

use App\Models\Licence;
use App\Models\LicencePayment;

beforeEach(function () {
    ensureRole('institution_user');
});

function createActiveInstitutionLicence(): array
{
    [, $institution] = actingAsInstitutionUserWithInstitution();

    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => null,
        'licence_number' => 'LIC-CERT-2026-001',
        'licence_year' => 2026,
        'licence_status' => 'active',
        'payment_status' => 'paid',
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'amount_due' => 100_000,
        'amount_paid' => 100_000,
        'outstanding_amount' => 0,
        'issued_at' => now(),
    ]);

    return [$institution, $licence];
}

it('downloads a licence certificate pdf for an active licence', function () {
    [, $licence] = createActiveInstitutionLicence();

    $response = $this->get('/api/v1/institution/licences/'.$licence->id.'/certificate', [
        'Accept' => 'application/pdf',
    ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
    expect(strlen($response->getContent()))->toBeGreaterThan(800);
});

it('forbids licence certificate when status is not active or expired', function () {
    [, $institution] = actingAsInstitutionUserWithInstitution();

    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => null,
        'licence_number' => 'LIC-PEND-001',
        'licence_year' => 2026,
        'licence_status' => 'pending_payment',
        'payment_status' => 'pending',
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'amount_due' => 50_000,
        'amount_paid' => 0,
        'outstanding_amount' => 50_000,
    ]);

    $this->get('/api/v1/institution/licences/'.$licence->id.'/certificate', [
        'Accept' => 'application/pdf',
    ])->assertForbidden();
});

it('downloads a payment receipt pdf for a paid payment', function () {
    [, $licence] = createActiveInstitutionLicence();

    $payment = LicencePayment::query()->create([
        'licence_id' => $licence->id,
        'institution_id' => $licence->institution_id,
        'institution_annual_declaration_id' => $licence->institution_annual_declaration_id,
        'invoice_id' => null,
        'payment_reference' => 'PAY-RCPT-'.uniqid(),
        'gateway_reference' => 'GW-REF-99',
        'provider_event_id' => null,
        'gateway_name' => 'paystack',
        'amount' => 25_000,
        'amount_allocated' => 25_000,
        'balance_before' => 50_000,
        'balance_after' => 25_000,
        'currency' => 'NGN',
        'payment_status' => 'paid',
        'paid_at' => now(),
        'processed_at' => now(),
        'raw_response_json' => null,
    ]);

    $response = $this->get('/api/v1/institution/payments/'.$payment->id.'/receipt', [
        'Accept' => 'application/pdf',
    ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
    expect(strlen($response->getContent()))->toBeGreaterThan(600);
});

it('forbids payment receipt when payment is not paid', function () {
    [, $licence] = createActiveInstitutionLicence();

    $payment = LicencePayment::query()->create([
        'licence_id' => $licence->id,
        'institution_id' => $licence->institution_id,
        'institution_annual_declaration_id' => $licence->institution_annual_declaration_id,
        'invoice_id' => null,
        'payment_reference' => 'PAY-PEND-'.uniqid(),
        'gateway_reference' => null,
        'provider_event_id' => null,
        'gateway_name' => 'paystack',
        'amount' => 25_000,
        'amount_allocated' => 0,
        'balance_before' => 50_000,
        'balance_after' => 50_000,
        'currency' => 'NGN',
        'payment_status' => 'pending',
        'paid_at' => null,
        'processed_at' => null,
        'raw_response_json' => null,
    ]);

    $this->get('/api/v1/institution/payments/'.$payment->id.'/receipt', [
        'Accept' => 'application/pdf',
    ])->assertForbidden();
});
