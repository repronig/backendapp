<?php

namespace App\Actions\Licensing;

use App\Models\Invoice;

class MarkInvoiceOverdueAction
{
    public function __construct(protected SyncInvoiceStateAction $syncInvoiceStateAction)
    {
    }

    public function execute(Invoice $invoice): Invoice
    {
        return $this->syncInvoiceStateAction->execute($invoice);
    }
}
