<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Models\LicencePayment;
use Illuminate\Support\Facades\DB;

class SyncInvoiceFromPaymentAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected SyncInvoiceStateAction $syncInvoiceStateAction
    ) {}

    public function execute(LicencePayment $payment): void
    {
        if (! $payment->invoice_id || $payment->payment_status !== 'paid') {
            return;
        }

        DB::transaction(function () use ($payment): void {
            $invoice = $payment->invoice()->lockForUpdate()->first();

            if (! $invoice) {
                return;
            }

            $before = $invoice->toArray();
            $freshInvoice = $this->syncInvoiceStateAction->execute($invoice);

            $this->logAuditAction->execute(null, 'invoice_synced_from_payment', $freshInvoice, $before, $freshInvoice->toArray(), null, null);
        });
    }
}
