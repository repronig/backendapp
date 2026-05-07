<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\Invoice;

class InvoiceGeneratedMailable extends BaseAppMailable
{
    public function __construct(public Invoice $invoice) {}
    protected function subjectLine(): string { return 'New Licence Invoice Issued'; }
    protected function viewName(): string { return 'emails.institutions.invoice-generated'; }
    protected function viewData(): array { return ['invoice' => $this->invoice]; }
}
