<?php

namespace App\Jobs;

use App\Models\LicencePayment;
use App\Models\User;
use App\Notifications\System\PaymentReceivedAdminSystemNotification;
use App\Notifications\System\PaymentReceivedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPaymentReceivedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $licencePaymentId) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        $payment = LicencePayment::query()
            ->with(['institution.institutionUsers.user', 'invoice', 'licence', 'declaration'])
            ->find($this->licencePaymentId);

        if (! $payment) {
            return;
        }

        $institution = $payment->institution;

        if ($institution !== null) {
            $recipient = (string) ($institution->email ?? '');
            if ($recipient === '') {
                foreach ($institution->institutionUsers as $institutionUser) {
                    if ($institutionUser->is_active && $institutionUser->user?->email) {
                        $recipient = (string) $institutionUser->user->email;
                        break;
                    }
                }
            }

            if ($recipient !== '') {
                $mailService->sendPaymentReceived($recipient, $payment);
            }

            foreach ($institution->institutionUsers as $institutionUser) {
                if (! $institutionUser->is_active || ! $institutionUser->user) {
                    continue;
                }

                $systemNotifications->send(
                    $institutionUser->user,
                    new PaymentReceivedSystemNotification(
                        (string) $payment->amount,
                        $payment->payment_reference,
                        $payment->id
                    ),
                    'payment_received',
                    'Payment received'
                );
            }
        }

        $currencyPrefix = strtoupper((string) $payment->currency) === 'NGN' ? '₦' : $payment->currency.' ';
        $allocated = (float) ($payment->amount_allocated ?: $payment->amount);
        $amountLabel = $currencyPrefix.number_format($allocated, 2);
        $institutionName = (string) ($institution?->name ?? 'Institution');

        $admins = User::adminAlertRecipients();
        foreach ($admins as $admin) {
            if ($admin->email) {
                $mailService->sendPaymentReceivedAdminNotice($admin, $payment);
            }

            $systemNotifications->send(
                $admin,
                new PaymentReceivedAdminSystemNotification(
                    $institutionName,
                    $amountLabel,
                    $payment->payment_reference,
                    $payment->id
                ),
                'payment_received_admin',
                'Institution payment received'
            );
        }
    }
}
