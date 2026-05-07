<?php

use App\Models\Invoice;
use App\Models\Licence;
use App\Models\LicencePayment;

beforeEach(function () {
    ensureRole('institution_user');
});

it('keeps institution licences list envelope and pagination contract stable', function () {
    [, $institution] = actingAsInstitutionUserWithInstitution();

    Licence::factory()->create([
        'institution_id' => $institution->id,
        'licence_status' => 'draft',
    ]);

    $this->getJson('/api/v1/institution/licences?per_page=50')
        ->assertOk()
        ->assertJsonPath('message', 'Licences retrieved successfully.')
        ->assertJsonPath('meta.per_page', 50)
        ->assertJsonStructure([
            'message',
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});

it('keeps institution invoices list envelope and pagination contract stable', function () {
    [, $institution] = actingAsInstitutionUserWithInstitution();

    Invoice::factory()->create([
        'institution_id' => $institution->id,
        'invoice_status' => 'issued',
    ]);

    $this->getJson('/api/v1/institution/invoices?per_page=20')
        ->assertOk()
        ->assertJsonPath('message', 'Invoices retrieved successfully.')
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonStructure([
            'message',
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});

it('keeps institution invoice detail envelope stable', function () {
    [, $institution] = actingAsInstitutionUserWithInstitution();

    $invoice = Invoice::factory()->create([
        'institution_id' => $institution->id,
        'invoice_status' => 'issued',
    ]);

    $this->getJson("/api/v1/institution/invoices/{$invoice->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Invoice retrieved successfully.')
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'invoice_number',
                'status',
                'total_amount',
                'outstanding_amount',
            ],
        ]);
});

it('keeps institution licence payments list envelope stable', function () {
    [, $institution] = actingAsInstitutionUserWithInstitution();

    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
    ]);

    LicencePayment::factory()->create([
        'institution_id' => $institution->id,
        'licence_id' => $licence->id,
    ]);

    $this->getJson("/api/v1/institution/licences/{$licence->id}/payments")
        ->assertOk()
        ->assertJsonPath('message', 'Licence payments retrieved successfully.')
        ->assertJsonStructure([
            'message',
            'data',
        ]);
});
