<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\LicencePaymentStatus;
use App\Events\LicencePaymentReceived;
use App\Models\LicencePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmOfflineLicencePaymentAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected SyncInvoiceFromPaymentAction $syncInvoiceFromPaymentAction,
    ) {}

    public function execute(LicencePayment $payment, User $admin, ?string $note = null, ?string $ipAddress = null, ?string $userAgent = null): LicencePayment
    {
        if ($payment->gateway_name !== 'offline') {
            throw ValidationException::withMessages(['payment' => ['Only offline payments can be confirmed from this action.']]);
        }

        if ($payment->payment_status !== LicencePaymentStatus::PendingOffline->value) {
            throw ValidationException::withMessages(['payment' => ['This offline payment is not awaiting confirmation.']]);
        }

        return DB::transaction(function () use ($payment, $admin, $note, $ipAddress, $userAgent): LicencePayment {
            $locked = LicencePayment::query()
                ->with(['invoice', 'licence', 'declaration', 'institution'])
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($locked->payment_status !== LicencePaymentStatus::PendingOffline->value) {
                throw ValidationException::withMessages(['payment' => ['This offline payment is no longer awaiting confirmation.']]);
            }

            $before = $locked->toArray();
            $amountPaid = round((float) $locked->amount, 2);

            $raw = (array) ($locked->raw_response_json ?? []);
            $offline = (array) ($raw['offline'] ?? []);
            $offline['confirmed_at'] = now()->toIso8601String();
            $offline['confirmed_by_user_id'] = $admin->id;
            if ($note !== null && $note !== '') {
                $offline['admin_note'] = $note;
            }
            $raw['offline'] = $offline;

            $locked->update([
                'payment_status' => LicencePaymentStatus::Paid->value,
                'amount_allocated' => $amountPaid,
                'paid_at' => now(),
                'processed_at' => now(),
                'balance_after' => max(round((float) $locked->balance_before - $amountPaid, 2), 0),
                'reconciled_at' => now(),
                'reconciled_by_user_id' => $admin->id,
                'is_reconciled' => true,
                'raw_response_json' => $raw,
            ]);

            $fresh = $locked->fresh(['invoice', 'licence', 'declaration', 'institution']);
            $this->syncInvoiceFromPaymentAction->execute($fresh);

            $this->logAuditAction->execute(
                $admin,
                'offline_licence_payment_confirmed',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            event(new LicencePaymentReceived($fresh->fresh(['invoice', 'licence', 'declaration', 'institution'])));

            return $fresh->fresh(['invoice', 'licence', 'declaration', 'institution']);
        });
    }
}
