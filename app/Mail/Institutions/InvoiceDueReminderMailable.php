<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\Invoice;

class InvoiceDueReminderMailable extends BaseAppMailable
{
    public function __construct(public Invoice $invoice) {}
    protected function subjectLine(): string { return 'Invoice Due Reminder'; }
    protected function viewName(): string { return 'emails.institutions.invoice-due-reminder'; }
    protected function viewData(): array { return ['invoice' => $this->invoice]; }
}
