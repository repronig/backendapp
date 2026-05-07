<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\Invoice;

class InvoiceOverdueReminderMailable extends BaseAppMailable
{
    public function __construct(public Invoice $invoice) {}
    protected function subjectLine(): string { return 'Overdue Invoice Notice'; }
    protected function viewName(): string { return 'emails.institutions.invoice-overdue-reminder'; }
    protected function viewData(): array { return ['invoice' => $this->invoice]; }
}
