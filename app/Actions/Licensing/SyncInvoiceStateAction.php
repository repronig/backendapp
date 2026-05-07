<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\InvoiceStatus;
use App\Enums\LicencePaymentStatus;
use App\Enums\LicencePaymentSummaryStatus;
use App\Enums\LicenceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class SyncInvoiceStateAction
{
    public function __construct(protected LogAuditAction $logAuditAction)
    {
    }

    public function execute(Invoice $invoice, bool $writeAudit = false, ?string $ipAddress = null, ?string $userAgent = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $writeAudit, $ipAddress, $userAgent): Invoice {
            $lockedInvoice = Invoice::query()
                ->with(['payments', 'licence.payments', 'declaration'])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $before = $lockedInvoice->toArray();
            $amountPaid = round((float) $lockedInvoice->payments()->where('payment_status', LicencePaymentStatus::Paid->value)->sum('amount_allocated'), 2);
            $outstanding = max(round((float) $lockedInvoice->total_amount - $amountPaid, 2), 0);
            $status = $this->resolveStatus($lockedInvoice, $amountPaid, $outstanding);

            $lockedInvoice->forceFill([
                'amount_paid' => $amountPaid,
                'outstanding_amount' => $outstanding,
                'invoice_status' => $status,
                'paid_at' => $outstanding <= 0 ? ($lockedInvoice->paid_at ?? now()) : null,
            ])->save();

            $licence = $lockedInvoice->licence;
            if ($licence) {
                $licencePaid = round((float) $licence->payments()->where('payment_status', LicencePaymentStatus::Paid->value)->sum('amount_allocated'), 2);
                $licenceOutstanding = max(round((float) $licence->amount_due - $licencePaid, 2), 0);

                $licence->update([
                    'amount_paid' => $licencePaid,
                    'outstanding_amount' => $licenceOutstanding,
                    'payment_status' => $licenceOutstanding <= 0 ? LicencePaymentSummaryStatus::Paid->value : ($licencePaid > 0 ? LicencePaymentSummaryStatus::PartiallyPaid->value : LicencePaymentSummaryStatus::Pending->value),
                    'licence_status' => $licenceOutstanding <= 0 ? LicenceStatus::Active->value : $licence->licence_status,
                ]);
            }

            $declaration = $lockedInvoice->declaration;
            if ($declaration) {
                $declaration->update([
                    'paid_amount' => $amountPaid,
                    'outstanding_amount' => $outstanding,
                ]);
            }

            $fresh = $lockedInvoice->fresh(['institution', 'declaration', 'licence']);

            if ($writeAudit && $before !== $fresh->toArray()) {
                $this->logAuditAction->execute(
                    null,
                    'invoice_state_synced',
                    $fresh,
                    $before,
                    $fresh->toArray(),
                    $ipAddress,
                    $userAgent
                );
            }

            return $fresh;
        });
    }

    protected function resolveStatus(Invoice $invoice, float $amountPaid, float $outstanding): string
    {
        if ($invoice->invoice_status === InvoiceStatus::Cancelled->value) {
            return InvoiceStatus::Cancelled->value;
        }

        if ($outstanding <= 0) {
            return InvoiceStatus::Paid->value;
        }

        if ($invoice->due_date && now()->startOfDay()->gt($invoice->due_date->startOfDay())) {
            return InvoiceStatus::Overdue->value;
        }

        return $amountPaid > 0 ? InvoiceStatus::PartiallyPaid->value : InvoiceStatus::Issued->value;
    }
}
