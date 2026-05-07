<?php

namespace App\Actions\Finance;

use App\Actions\Audit\LogAuditAction;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyInvoiceAdjustmentAction
{
    public function __construct(protected LogAuditAction $logAuditAction) {}

    public function execute(Invoice $invoice, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): Invoice
    {
        if (blank($data['reason'] ?? null) || blank($data['reason_code'] ?? null)) {
            throw ValidationException::withMessages([
                'reason' => ['Reason and reason code are required for invoice adjustments.'],
            ]);
        }

        return DB::transaction(function () use ($invoice, $data, $actor, $ipAddress, $userAgent): Invoice {
            $before = $invoice->toArray();

            $amount = round((float) $data['amount'], 2);
            $currentTotal = round((float) $invoice->total_amount, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Adjustment amount must be greater than zero.'],
                ]);
            }

            if ($amount > $currentTotal) {
                throw ValidationException::withMessages([
                    'amount' => ['Adjustment cannot exceed the invoice total.'],
                ]);
            }

            InvoiceAdjustment::create([
                'invoice_id' => $invoice->id,
                'created_by_user_id' => $actor->id,
                'adjustment_type' => $data['adjustment_type'],
                'amount' => $amount,
                'reason_code' => $data['reason_code'],
                'reason' => $data['reason'],
                'metadata_json' => $data['metadata_json'] ?? null,
                'applied_at' => now(),
            ]);

            // Credit-style adjustments (credit_note, manual_adjustment) reduce what is owed by lowering
            // invoice totals. Outstanding is recomputed as total_amount − amount_paid in syncStatus().
            $invoice->subtotal_amount = max(round((float) $invoice->subtotal_amount - $amount, 2), 0);
            $invoice->total_amount = max($currentTotal - $amount, 0);
            $invoice->syncStatus();

            $fresh = $invoice->fresh(['adjustments']);

            $this->logAuditAction->execute(
                $actor,
                'invoice_adjusted',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent,
            );

            return $fresh;
        });
    }
}
