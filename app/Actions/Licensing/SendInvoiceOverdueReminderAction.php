<?php

namespace App\Actions\Licensing;

use App\Models\Invoice;
use App\Notifications\System\InvoiceOverdueReminderSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;

class SendInvoiceOverdueReminderAction
{
    public function __construct(
        protected ShouldSendInvoiceOverdueReminderAction $shouldSendInvoiceOverdueReminderAction,
        protected MailService $mailService,
        protected SystemNotificationService $systemNotifications,
    ) {}

    public function execute(Invoice $invoice): void
    {
        $invoice->loadMissing('institution.institutionUsers.user');

        if (! $this->shouldSendInvoiceOverdueReminderAction->execute($invoice)) {
            return;
        }

        $forMail = $invoice->fresh(['institution', 'declaration', 'licence']);

        $this->mailService->sendInvoiceOverdueReminder($forMail);

        $symbol = strtoupper((string) $forMail->currency) === 'NGN' ? '₦' : $forMail->currency.' ';
        $outstandingFormatted = $symbol.number_format((float) $forMail->outstanding_amount, 2);

        foreach ($forMail->institution->institutionUsers ?? [] as $institutionUser) {
            if (! $institutionUser->is_active || ! $institutionUser->user) {
                continue;
            }

            $this->systemNotifications->send(
                $institutionUser->user,
                new InvoiceOverdueReminderSystemNotification(
                    $forMail->invoice_number,
                    $outstandingFormatted,
                    $forMail->id,
                ),
                'invoice_overdue_reminder',
                'Invoice overdue'
            );
        }

        $invoice->forceFill(['last_overdue_reminder_sent_at' => now()])->save();
    }
}
