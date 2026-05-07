<?php

namespace App\Actions\Finance;

use App\Actions\Audit\LogAuditAction;
use App\Models\LicencePayment;
use App\Models\User;

class RecordPaymentReconciliationAction
{
    public function __construct(protected LogAuditAction $logAuditAction)
    {
    }

    public function execute(LicencePayment $payment, array $data, ?User $actor = null, ?string $ipAddress = null, ?string $userAgent = null): LicencePayment
    {
        $before = $payment->toArray();

        $payment->reconciliations()->create([
            'invoice_id' => $payment->invoice_id,
            'processed_by_user_id' => $actor?->id,
            'status' => $data['status'],
            'reason_code' => $data['reason_code'] ?? null,
            'note' => $data['note'] ?? null,
            'before_json' => $before,
            'after_json' => array_merge($before, ['is_reconciled' => true]),
            'processed_at' => now(),
        ]);

        $payment->forceFill([
            'is_reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by_user_id' => $actor?->id,
        ])->save();

        $fresh = $payment->fresh(['reconciliations']);

        $this->logAuditAction->execute($actor, 'payment_reconciled', $fresh, $before, $fresh->toArray(), $ipAddress, $userAgent);

        return $fresh;
    }
}
