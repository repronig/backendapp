<?php

namespace App\Jobs;

use App\Models\LicencePayment;
use App\Models\User;
use App\Notifications\System\OfflineInvoicePaymentSubmittedAdminSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOfflineInvoicePaymentSubmittedAdminNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $licencePaymentId) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        if ($this->licencePaymentId < 1) {
            return;
        }

        $payment = LicencePayment::query()
            ->with(['institution', 'invoice'])
            ->find($this->licencePaymentId);

        if (! $payment) {
            return;
        }

        $institutionName = (string) ($payment->institution?->name ?: 'An institution');
        $amountLabel = $this->formatAmountLabel($payment);
        $admins = User::adminAlertRecipients();

        foreach ($admins as $admin) {
            if ($admin->email) {
                $mailService->sendOfflineInvoicePaymentSubmittedToAdmin($admin, $payment);
            }

            $systemNotifications->send(
                $admin,
                new OfflineInvoicePaymentSubmittedAdminSystemNotification(
                    $institutionName,
                    $amountLabel,
                    (string) ($payment->payment_reference ?? ''),
                    (int) $payment->id
                ),
                'offline_invoice_payment_submitted_admin',
                'Offline payment submitted for review'
            );
        }
    }

    protected function formatAmountLabel(LicencePayment $payment): string
    {
        $currency = strtoupper((string) ($payment->currency ?? ''));
        $prefix = $currency === 'NGN' ? '₦' : (($currency !== '' ? $currency : 'NGN').' ');
        $amount = (float) ($payment->amount ?? 0);

        return $prefix.number_format($amount, 2);
    }
}
