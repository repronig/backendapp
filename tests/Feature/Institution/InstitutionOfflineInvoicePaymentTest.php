<?php

use App\Mail\Admin\OfflineInvoicePaymentSubmittedAdminMailable;
use App\Mail\Institutions\OfflineLicencePaymentRejectedMailable;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\Invoice;
use App\Models\Licence;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    ensureRole('institution_user');
    ensureRole('admin');
    Storage::fake((string) config('filesystems.default', 'local'));
});

function seedOfflineEnabledLicensing(): void
{
    Setting::query()->updateOrCreate(
        ['key' => 'licensing'],
        ['value' => [
            'paystack_enabled' => true,
            'flutterwave_enabled' => true,
            'offline_payment_enabled' => true,
            'default_online_gateway' => 'paystack',
            'repronig_bank' => [],
            'institution_licensing_terms' => [],
        ]]
    );
}

it('allows an institution user to submit an offline invoice payment for review', function () {
    Mail::fake();
    seedOfflineEnabledLicensing();
    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email' => 'offline-admin@example.test',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');
    [$user, $institution] = actingAsInstitutionUserWithInstitution();

    $declaration = InstitutionAnnualDeclaration::factory()->approved()->create([
        'institution_id' => $institution->id,
        'expected_amount' => 10000,
        'outstanding_amount' => 10000,
        'paid_amount' => 0,
    ]);

    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id_snapshot' => $declaration->licence_id_snapshot,
        'licence_year' => $declaration->licensing_year,
        'licence_number' => 'TEST-LIC-OFFLINE-1',
        'licence_status' => 'pending_payment',
        'payment_status' => 'pending',
        'amount_due' => 10000,
        'amount_paid' => 0,
        'outstanding_amount' => 10000,
    ]);

    $invoice = Invoice::create([
        'invoice_number' => 'INV-OFFLINE-TEST-001',
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id' => $licence->id,
        'invoice_type' => 'licence_fee',
        'billing_year' => $declaration->licensing_year,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'subtotal_amount' => 10000,
        'total_amount' => 10000,
        'amount_paid' => 0,
        'outstanding_amount' => 10000,
        'invoice_status' => 'issued',
        'currency' => 'NGN',
        'issued_at' => now(),
    ]);

    $file = UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf');

    $this->postJson("/api/v1/institution/invoices/{$invoice->id}/offline-payments", [
        'amount' => 5000,
        'paid_in_full' => false,
        'institution_note' => 'Bank transfer ref ABC',
        'receipt' => $file,
    ])->assertCreated()
        ->assertJsonPath('data.gateway_name', 'offline')
        ->assertJsonPath('data.payment_status', 'pending_offline')
        ->assertJsonPath('data.amount', '5000.00');

    $payment = \App\Models\LicencePayment::query()->latest('id')->first();
    expect(data_get($payment?->raw_response_json, 'offline.proof_disk'))
        ->toBe((string) config('filesystems.default', 'local'));

    expect((float) $invoice->fresh()->outstanding_amount)->toBe(10000.0);
    Mail::assertQueued(OfflineInvoicePaymentSubmittedAdminMailable::class, 1);
    expect(NotificationLog::query()
        ->where('notification_key', 'offline_invoice_payment_submitted_admin')
        ->where('channel', 'email')
        ->where('user_id', $admin->id)
        ->exists())->toBeTrue()
        ->and(NotificationLog::query()
            ->where('notification_key', 'offline_invoice_payment_submitted_admin')
            ->where('channel', 'system')
            ->where('user_id', $admin->id)
            ->exists())->toBeTrue();
});

it('allows an admin to confirm an offline payment and sync the invoice', function () {
    seedOfflineEnabledLicensing();
    [$instUser, $institution] = actingAsInstitutionUserWithInstitution();

    $declaration = InstitutionAnnualDeclaration::factory()->approved()->create([
        'institution_id' => $institution->id,
        'expected_amount' => 10000,
        'outstanding_amount' => 10000,
        'paid_amount' => 0,
    ]);

    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id_snapshot' => $declaration->licence_id_snapshot,
        'licence_year' => $declaration->licensing_year,
        'licence_number' => 'TEST-LIC-OFFLINE-2',
        'licence_status' => 'pending_payment',
        'payment_status' => 'pending',
        'amount_due' => 10000,
        'amount_paid' => 0,
        'outstanding_amount' => 10000,
    ]);

    $invoice = Invoice::create([
        'invoice_number' => 'INV-OFFLINE-TEST-002',
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id' => $licence->id,
        'invoice_type' => 'licence_fee',
        'billing_year' => $declaration->licensing_year,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'subtotal_amount' => 10000,
        'total_amount' => 10000,
        'amount_paid' => 0,
        'outstanding_amount' => 10000,
        'invoice_status' => 'issued',
        'currency' => 'NGN',
        'issued_at' => now(),
    ]);

    Sanctum::actingAs($instUser);

    $submit = $this->postJson("/api/v1/institution/invoices/{$invoice->id}/offline-payments", [
        'amount' => 10000,
        'paid_in_full' => true,
        'receipt' => UploadedFile::fake()->create('proof.pdf', 200, 'application/pdf'),
    ])->assertCreated();

    $paymentId = (int) $submit->json('data.id');

    $admin = actingAsApiUser('admin', ['account_type' => 'admin']);
    $admin->forceFill(['last_security_confirmation_at' => now()])->save();

    $this->postJson("/api/v1/admin/payments/{$paymentId}/offline/confirm", ['note' => 'Matched in bank'])
        ->assertOk()
        ->assertJsonPath('data.payment_status', 'paid');

    $invoice->refresh();
    expect((float) $invoice->amount_paid)->toBe(10000.0)
        ->and((float) $invoice->outstanding_amount)->toBe(0.0)
        ->and($invoice->invoice_status)->toBe('paid');
});

it('notifies institution users by email and system when an offline payment is rejected', function () {
    Mail::fake();
    seedOfflineEnabledLicensing();
    [$instUser, $institution] = actingAsInstitutionUserWithInstitution();

    // Force email to be routed to the institution user (not institution.email)
    $institution->forceFill(['email' => null])->save();

    $declaration = InstitutionAnnualDeclaration::factory()->approved()->create([
        'institution_id' => $institution->id,
        'expected_amount' => 10000,
        'outstanding_amount' => 10000,
        'paid_amount' => 0,
    ]);

    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id_snapshot' => $declaration->licence_id_snapshot,
        'licence_year' => $declaration->licensing_year,
        'licence_number' => 'TEST-LIC-OFFLINE-REJECT-1',
        'licence_status' => 'pending_payment',
        'payment_status' => 'pending',
        'amount_due' => 10000,
        'amount_paid' => 0,
        'outstanding_amount' => 10000,
    ]);

    $invoice = Invoice::create([
        'invoice_number' => 'INV-OFFLINE-TEST-REJECT-001',
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id' => $licence->id,
        'invoice_type' => 'licence_fee',
        'billing_year' => $declaration->licensing_year,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'subtotal_amount' => 10000,
        'total_amount' => 10000,
        'amount_paid' => 0,
        'outstanding_amount' => 10000,
        'invoice_status' => 'issued',
        'currency' => 'NGN',
        'issued_at' => now(),
    ]);

    Sanctum::actingAs($instUser);
    $submit = $this->postJson("/api/v1/institution/invoices/{$invoice->id}/offline-payments", [
        'amount' => 5000,
        'paid_in_full' => false,
        'receipt' => UploadedFile::fake()->create('proof.pdf', 200, 'application/pdf'),
    ])->assertCreated();

    $paymentId = (int) $submit->json('data.id');

    $admin = actingAsApiUser('admin', ['account_type' => 'admin']);
    $admin->forceFill(['last_security_confirmation_at' => now()])->save();

    $this->postJson("/api/v1/admin/payments/{$paymentId}/offline/reject", ['reason' => 'Receipt not valid'])
        ->assertOk()
        ->assertJsonPath('data.payment_status', 'cancelled');

    Mail::assertQueued(OfflineLicencePaymentRejectedMailable::class, fn () => true);

    expect(NotificationLog::query()
        ->where('notification_key', 'offline_licence_payment_rejected')
        ->where('channel', 'email')
        ->where('user_id', $instUser->id)
        ->exists())->toBeTrue()
        ->and(NotificationLog::query()
            ->where('notification_key', 'offline_licence_payment_rejected')
            ->where('channel', 'system')
            ->where('user_id', $instUser->id)
            ->exists())->toBeTrue();
});

it('rejects offline submission when offline payments are disabled', function () {
    Setting::query()->updateOrCreate(
        ['key' => 'licensing'],
        ['value' => [
            'offline_payment_enabled' => false,
        ]]
    );

    [$user, $institution] = actingAsInstitutionUserWithInstitution();
    $declaration = InstitutionAnnualDeclaration::factory()->approved()->create(['institution_id' => $institution->id]);
    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id_snapshot' => $declaration->licence_id_snapshot,
        'licence_year' => $declaration->licensing_year,
        'licence_number' => 'TEST-LIC-OFFLINE-3',
        'licence_status' => 'pending_payment',
        'payment_status' => 'pending',
        'amount_due' => 5000,
        'amount_paid' => 0,
        'outstanding_amount' => 5000,
    ]);
    $invoice = Invoice::create([
        'invoice_number' => 'INV-OFFLINE-TEST-003',
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id' => $licence->id,
        'invoice_type' => 'licence_fee',
        'billing_year' => $declaration->licensing_year,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'subtotal_amount' => 5000,
        'total_amount' => 5000,
        'amount_paid' => 0,
        'outstanding_amount' => 5000,
        'invoice_status' => 'issued',
        'currency' => 'NGN',
        'issued_at' => now(),
    ]);

    $this->postJson("/api/v1/institution/invoices/{$invoice->id}/offline-payments", [
        'amount' => 1000,
        'receipt' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
    ])->assertUnprocessable();
});
