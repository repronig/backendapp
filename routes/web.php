<?php

use App\Models\Licence;
use App\Models\LicencePayment;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/preview-email/member-approved', function () {
    $user = User::first() ?? new User(['first_name' => 'Test']);
    return view('emails.members.application-approved', [
        'user' => $user,
        'memberCode' => 'MEM-000123',
    ]);
});

Route::get('/preview-email/member-rejected', function () {
    $user = User::first() ?? new User(['first_name' => 'Test']);
    return view('emails.members.application-rejected', [
        'user' => $user,
        'reason' => 'The submitted supporting documents were incomplete.',
    ]);
});

Route::get('/preview-email/member-changes-requested', function () {
    $user = User::first() ?? new User(['first_name' => 'Test']);
    return view('emails.members.application-changes-requested', [
        'user' => $user,
        'comment' => 'Please upload a clearer proof of address and confirm your application details.',
    ]);
});

Route::get('/preview-email/payment-initiated', function () {
    $user = User::first() ?? new User(['first_name' => 'Test']);
    $licence = Licence::first() ?? new Licence(['licence_year' => 2026, 'licence_number' => 'LIC-2026-001']);
    $payment = LicencePayment::first() ?? new LicencePayment(['payment_reference' => 'PAY-001', 'amount' => 50000]);
    return view('emails.licensing.payment-initiated', [
        'user' => $user,
        'licence' => $licence,
        'payment' => $payment,
        'paymentUrl' => config('app.frontend_url') . '/payments/demo',
    ]);
});

Route::get('/preview-email/payment-confirmed', function () {
    $user = User::first() ?? new User(['first_name' => 'Test']);
    $licence = Licence::first() ?? new Licence(['licence_year' => 2026, 'licence_number' => 'LIC-2026-001']);
    $payment = LicencePayment::first() ?? new LicencePayment(['payment_reference' => 'PAY-001', 'amount' => 50000]);
    return view('emails.licensing.payment-confirmed', [
        'user' => $user,
        'licence' => $licence,
        'payment' => $payment,
    ]);
});

Route::get('/preview-email/usage-reminder', function () {
    $user = User::first() ?? new User(['first_name' => 'Test']);
    $licence = Licence::first() ?? new Licence(['licence_year' => 2026, 'licence_number' => 'LIC-2026-001']);
    return view('emails.licensing.usage-declaration-reminder', [
        'user' => $user,
        'licence' => $licence,
        'declarationUrl' => config('app.frontend_url') . '/usage-declarations/demo',
    ]);
});


Route::get('/preview-email/member-approved', function () {
    $user = User::first() ?? new User(['first_name' => 'Test']);
    $licence = Licence::first() ?? new Licence(['licence_year' => 2026, 'licence_number' => 'LIC-2026-001']);
    return view('emails.members.application-approved', [
        'user' => $user,
        'memberCode' => 'your-member-code',
        'declarationUrl' => config('app.frontend_url') . '/usage-declarations/demo',
    ]);
});



/*
In reminder job / scheduler

$user->notify(
    new \App\Notifications\Licensing\UsageDeclarationReminderNotification(
        $licence,
        $declarationUrl ?? null
    )
);

a MailServiceProvider / helper pattern for cleaner sending from actions,
*/