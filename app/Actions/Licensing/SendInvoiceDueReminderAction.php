<?php

namespace App\Actions\Licensing;

use App\Models\Invoice;
use App\Notifications\System\InvoiceDueReminderSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;

class SendInvoiceDueReminderAction
{
    public function __construct(
        protected ShouldSendInvoiceDueReminderAction $shouldSendInvoiceDueReminderAction,
        protected MailService $mailService,
        protected SystemNotificationService $systemNotifications,
    ) {}

    public function execute(Invoice $invoice): void
    {
        $invoice->loadMissing('institution.institutionUsers.user');

        if (! $this->shouldSendInvoiceDueReminderAction->execute($invoice)) {
            return;
        }

        $forNotify = $invoice->fresh(['institution.institutionUsers.user', 'declaration', 'licence']);
        $this->mailService->sendInvoiceDueReminder($forNotify);

        $symbol = strtoupper((string) $forNotify->currency) === 'NGN' ? '₦' : $forNotify->currency.' ';
        $outstandingFormatted = $symbol.number_format((float) $forNotify->outstanding_amount, 2);
        $dueDate = optional($forNotify->due_date)->format('Y-m-d') ?? now()->toDateString();

        foreach ($forNotify->institution->institutionUsers ?? [] as $institutionUser) {
            if (! $institutionUser->is_active || ! $institutionUser->user) {
                continue;
            }

            $this->systemNotifications->send(
                $institutionUser->user,
                new InvoiceDueReminderSystemNotification(
                    $forNotify->invoice_number,
                    $dueDate,
                    $outstandingFormatted,
                    $forNotify->id
                ),
                'invoice_due_reminder',
                'Invoice due reminder'
            );
        }

        $invoice->forceFill(['last_due_reminder_sent_at' => now()])->save();
    }
}
