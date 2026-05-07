<?php

use App\Actions\Licensing\InitiateLicencePaymentAction;
use App\Actions\Licensing\RejectInstitutionAction;
use App\Actions\Licensing\RejectOfflineLicencePaymentAction;
use App\Actions\Licensing\SendInvoiceDueReminderAction;
use App\Actions\Licensing\SendInvoiceOverdueReminderAction;
use App\Actions\Licensing\ShouldSendInvoiceDueReminderAction;
use App\Actions\Licensing\ShouldSendInvoiceOverdueReminderAction;
use App\Actions\Licensing\SubmitInstitutionAnnualDeclarationAction;
use App\Actions\Super\EnableAssociationAction;
use App\Enums\LicencePaymentStatus;
use App\Models\Association;
use App\Models\Institution;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\InstitutionUser;
use App\Models\Invoice;
use App\Models\Licence;
use App\Models\LicencePayment;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    ensureRole('admin');
    ensureRole('super_admin');
});

function makeInstitutionWithActiveUser(): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['account_type' => 'institution_user']);

    InstitutionUser::factory()
        ->primary()
        ->create([
            'institution_id' => $institution->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

    return [$institution, $user];
}

it('logs exact notification_key values for institution rejection', function () {
    [$institution, $institutionUser] = makeInstitutionWithActiveUser();
    $actor = User::factory()->create(['account_type' => 'admin']);

    app(RejectInstitutionAction::class)->execute($institution, $actor, 'Incomplete profile');

    expect(NotificationLog::query()->where('notification_key', 'institution_rejected')->where('channel', 'email')->exists())->toBeTrue()
        ->and(NotificationLog::query()->where('notification_key', 'institution_rejected')->where('channel', 'system')->where('user_id', $institutionUser->id)->exists())->toBeTrue();
});

it('logs exact notification_key values for payment initiated', function () {
    [$institution, $institutionUser] = makeInstitutionWithActiveUser();
    $actor = User::factory()->create(['account_type' => 'admin']);

    Setting::query()->updateOrCreate(
        ['group' => 'general', 'key' => 'licensing'],
        ['value' => [
            'paystack_enabled' => true,
            'flutterwave_enabled' => false,
            'default_online_gateway' => 'paystack',
            'offline_payment_enabled' => true,
        ]]
    );

    $declaration = InstitutionAnnualDeclaration::factory()->approved()->create([
        'institution_id' => $institution->id,
        'expected_amount' => 10000,
        'outstanding_amount' => 10000,
    ]);

    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'amount_due' => 10000,
        'outstanding_amount' => 10000,
        'licence_status' => 'pending_payment',
        'payment_status' => 'pending',
    ]);

    $invoice = Invoice::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id' => $licence->id,
        'total_amount' => 10000,
        'outstanding_amount' => 10000,
        'invoice_status' => 'issued',
    ]);

    app(InitiateLicencePaymentAction::class)->execute($licence, $actor, 5000, $invoice);

    expect(NotificationLog::query()->where('notification_key', 'payment_initiated')->where('channel', 'email')->exists())->toBeTrue()
        ->and(NotificationLog::query()->where('notification_key', 'payment_initiated')->where('channel', 'system')->where('user_id', $institutionUser->id)->exists())->toBeTrue();
});

it('logs exact notification_key values for invoice due and overdue reminders', function () {
    [$institution, $institutionUser] = makeInstitutionWithActiveUser();

    $dueGuard = Mockery::mock(ShouldSendInvoiceDueReminderAction::class);
    $dueGuard->shouldReceive('execute')->andReturnTrue();
    app()->instance(ShouldSendInvoiceDueReminderAction::class, $dueGuard);

    $overdueGuard = Mockery::mock(ShouldSendInvoiceOverdueReminderAction::class);
    $overdueGuard->shouldReceive('execute')->andReturnTrue();
    app()->instance(ShouldSendInvoiceOverdueReminderAction::class, $overdueGuard);

    $declaration = InstitutionAnnualDeclaration::factory()->approved()->create([
        'institution_id' => $institution->id,
    ]);
    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
    ]);
    $invoice = Invoice::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id' => $licence->id,
        'invoice_status' => 'issued',
        'outstanding_amount' => 7000,
    ]);

    app(SendInvoiceDueReminderAction::class)->execute($invoice);
    app(SendInvoiceOverdueReminderAction::class)->execute($invoice);

    expect(NotificationLog::query()->where('notification_key', 'invoice_due_reminder')->where('channel', 'email')->exists())->toBeTrue()
        ->and(NotificationLog::query()->where('notification_key', 'invoice_due_reminder')->where('channel', 'system')->where('user_id', $institutionUser->id)->exists())->toBeTrue()
        ->and(NotificationLog::query()->where('notification_key', 'invoice_overdue_reminder')->where('channel', 'email')->exists())->toBeTrue()
        ->and(NotificationLog::query()->where('notification_key', 'invoice_overdue_reminder')->where('channel', 'system')->where('user_id', $institutionUser->id)->exists())->toBeTrue();
});

it('logs exact notification_key values for offline payment rejected', function () {
    [$institution, $institutionUser] = makeInstitutionWithActiveUser();
    $admin = User::factory()->create(['account_type' => 'admin']);

    $declaration = InstitutionAnnualDeclaration::factory()->approved()->create([
        'institution_id' => $institution->id,
    ]);
    $licence = Licence::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
    ]);
    $invoice = Invoice::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id' => $licence->id,
    ]);

    $payment = LicencePayment::factory()->create([
        'institution_id' => $institution->id,
        'institution_annual_declaration_id' => $declaration->id,
        'licence_id' => $licence->id,
        'invoice_id' => $invoice->id,
        'gateway_name' => 'offline',
        'payment_status' => LicencePaymentStatus::PendingOffline->value,
        'raw_response_json' => ['offline' => []],
    ]);

    app(RejectOfflineLicencePaymentAction::class)->execute($payment, $admin, 'Receipt not valid');

    expect(NotificationLog::query()->where('notification_key', 'offline_licence_payment_rejected')->where('channel', 'email')->exists())->toBeTrue()
        ->and(NotificationLog::query()->where('notification_key', 'offline_licence_payment_rejected')->where('channel', 'system')->where('user_id', $institutionUser->id)->exists())->toBeTrue();
});

it('logs exact notification_key values for institution declaration submitted to admins', function () {
    ensureRole('admin');
    $admin = User::factory()->create(['account_type' => 'admin']);
    $admin->assignRole('admin');

    $institution = Institution::factory()->create();
    $institutionUser = User::factory()->create(['account_type' => 'institution_user']);
    InstitutionUser::factory()->primary()->create([
        'institution_id' => $institution->id,
        'user_id' => $institutionUser->id,
        'is_active' => true,
    ]);

    $declaration = InstitutionAnnualDeclaration::factory()->withSupportingDocument()->create([
        'institution_id' => $institution->id,
    ]);

    app(SubmitInstitutionAnnualDeclarationAction::class)->execute($declaration, $institutionUser, '127.0.0.1', 'PHPUnit');

    expect(NotificationLog::query()->where('notification_key', 'institution_declaration_submitted')->where('channel', 'email')->where('user_id', $admin->id)->exists())->toBeTrue()
        ->and(NotificationLog::query()->where('notification_key', 'institution_declaration_submitted')->where('channel', 'system')->where('user_id', $admin->id)->exists())->toBeTrue();
});

it('logs exact notification_key values for association enabled', function () {
    $association = Association::factory()->create([
        'is_enabled' => false,
        'status' => 'inactive',
    ]);
    $officer = User::factory()->create(['account_type' => 'association_officer']);
    $association->users()->attach($officer->id, ['is_active' => true, 'designation_title' => 'Officer']);
    $actor = User::factory()->create(['account_type' => 'super_admin']);

    app(EnableAssociationAction::class)->execute($association, $actor);

    expect(NotificationLog::query()->where('notification_key', 'association_enabled')->where('channel', 'email')->exists())->toBeTrue()
        ->and(NotificationLog::query()->where('notification_key', 'association_enabled')->where('channel', 'system')->where('user_id', $officer->id)->exists())->toBeTrue();
});
