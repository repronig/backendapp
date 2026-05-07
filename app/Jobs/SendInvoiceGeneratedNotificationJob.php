<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Notifications\System\InvoiceGeneratedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendInvoiceGeneratedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function handle(MailService $mailService, SystemNotificationService $systemNotifications): void
    {
        $invoice = $this->invoice->fresh(['institution.institutionUsers.user']);

        $mailService->sendInvoiceGenerated($invoice);

        foreach ($invoice->institution->institutionUsers as $institutionUser) {
            if (! $institutionUser->is_active || ! $institutionUser->user) {
                continue;
            }

            $systemNotifications->send(
                $institutionUser->user,
                new InvoiceGeneratedSystemNotification(
                    $invoice->invoice_number,
                    (string) $invoice->total_amount,
                    optional($invoice->due_date)->format('Y-m-d'),
                    $invoice->id
                ),
                'invoice_generated',
                'Invoice generated'
            );
        }
    }
}
