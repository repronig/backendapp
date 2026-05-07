<?php

use App\Models\Invoice;

beforeEach(function () {
    ensureRole('admin');
});

it('reduces invoice total and outstanding when recording a credit adjustment on an unpaid invoice', function () {
    $user = actingAsApiUser('admin', ['account_type' => 'admin']);
    $user->forceFill(['last_security_confirmation_at' => now()])->save();

    $invoice = Invoice::factory()->create([
        'invoice_status' => 'issued',
        'subtotal_amount' => 10000,
        'total_amount' => 10000,
        'amount_paid' => 0,
        'outstanding_amount' => 10000,
    ]);

    $response = $this->postJson("/api/v1/admin/invoices/{$invoice->id}/adjustments", [
        'adjustment_type' => 'manual_adjustment',
        'amount' => 2500,
        'reason_code' => 'TEST-ADJ',
        'reason' => 'Test credit adjustment for automated coverage.',
    ])->assertOk();

    expect((float) $response->json('data.total_amount'))->toBe(7500.0)
        ->and((float) $response->json('data.outstanding_amount'))->toBe(7500.0);

    $invoice->refresh();
    expect((float) $invoice->total_amount)->toBe(7500.0)
        ->and((float) $invoice->subtotal_amount)->toBe(7500.0)
        ->and((float) $invoice->outstanding_amount)->toBe(7500.0);
});

it('reduces outstanding by the credit amount when part of the invoice is already paid', function () {
    $user = actingAsApiUser('admin', ['account_type' => 'admin']);
    $user->forceFill(['last_security_confirmation_at' => now()])->save();

    $invoice = Invoice::factory()->create([
        'invoice_status' => 'partially_paid',
        'subtotal_amount' => 10000,
        'total_amount' => 10000,
        'amount_paid' => 3000,
        'outstanding_amount' => 7000,
    ]);

    $response = $this->postJson("/api/v1/admin/invoices/{$invoice->id}/adjustments", [
        'adjustment_type' => 'credit_note',
        'amount' => 2000,
        'reason_code' => 'CN-001',
        'reason' => 'Issued credit note against licence fee.',
    ])->assertOk();

    expect((float) $response->json('data.total_amount'))->toBe(8000.0)
        ->and((float) $response->json('data.amount_paid'))->toBe(3000.0)
        ->and((float) $response->json('data.outstanding_amount'))->toBe(5000.0);

    $invoice->refresh();
    expect((float) $invoice->total_amount)->toBe(8000.0)
        ->and((float) $invoice->amount_paid)->toBe(3000.0)
        ->and((float) $invoice->outstanding_amount)->toBe(5000.0);
});

it('rejects an adjustment larger than the invoice total', function () {
    $user = actingAsApiUser('admin', ['account_type' => 'admin']);
    $user->forceFill(['last_security_confirmation_at' => now()])->save();

    $invoice = Invoice::factory()->create([
        'invoice_status' => 'issued',
        'subtotal_amount' => 1000,
        'total_amount' => 1000,
        'amount_paid' => 0,
        'outstanding_amount' => 1000,
    ]);

    $this->postJson("/api/v1/admin/invoices/{$invoice->id}/adjustments", [
        'adjustment_type' => 'manual_adjustment',
        'amount' => 1500,
        'reason_code' => 'X',
        'reason' => 'Too large.',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});
