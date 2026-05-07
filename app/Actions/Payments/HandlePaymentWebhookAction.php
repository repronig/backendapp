<?php

namespace App\Actions\Payments;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Licensing\SyncInvoiceFromPaymentAction;
use App\Enums\LicencePaymentStatus;
use App\Events\LicencePaymentReceived;
use App\Models\LicencePayment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HandlePaymentWebhookAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected SyncInvoiceFromPaymentAction $syncInvoiceFromPaymentAction
    ) {}

    public function execute(array $payload, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $event = (string) Arr::get($payload, 'event', '');
        $data = (array) Arr::get($payload, 'data', []);
        $reference = (string) (
            Arr::get($data, 'reference')
            ?: Arr::get($data, 'tx_ref')
            ?: Arr::get($payload, 'reference')
            ?: Arr::get($payload, 'tx_ref', '')
        );

        if ($reference === '') {
            throw ValidationException::withMessages(['reference' => ['Payment reference is required.']]);
        }

        return DB::transaction(function () use ($payload, $event, $reference, $ipAddress, $userAgent) {
            $payment = LicencePayment::query()
                ->with(['invoice', 'licence', 'declaration', 'institution'])
                ->where('payment_reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();
            $before = $payment->toArray();

            if ($payment->payment_status === LicencePaymentStatus::Paid->value) {
                $fresh = $payment->fresh(['invoice', 'licence', 'declaration', 'institution']);
                $this->syncInvoiceFromPaymentAction->execute($fresh);

                return ['payment' => $payment->fresh(['invoice', 'licence', 'declaration', 'institution']), 'already_processed' => true];
            }

            $gateway = (string) $payment->gateway_name;
            $status = strtolower((string) (Arr::get($payload, 'data.status') ?: Arr::get($payload, 'status', '')));
            $isSuccessful = $gateway === 'flutterwave'
                ? in_array($status, ['successful', 'success'], true) || in_array($event, ['charge.completed', 'charge.success'], true)
                : $event === 'charge.success' || $status === 'success';

            if (! $isSuccessful) {
                $payment->update(['payment_status' => LicencePaymentStatus::Failed->value, 'raw_response_json' => $payload]);

                $this->logAuditAction->execute(
                    null,
                    'licence_payment_webhook_failed',
                    $payment->fresh(['invoice', 'licence', 'declaration', 'institution']),
                    $before,
                    $payment->fresh()->toArray(),
                    $ipAddress,
                    $userAgent
                );

                return ['payment' => $payment->fresh(), 'already_processed' => false];
            }

            $amountPaid = round((float) Arr::get($payload, 'data.amount', Arr::get($payload, 'amount', 0)), 2);
            if ($gateway !== 'flutterwave') {
                $amountPaid = round($amountPaid / 100, 2);
            }

            if ($amountPaid <= 0) {
                $amountPaid = round((float) $payment->amount, 2);
            }

            $payment->update([
                'gateway_reference' => (string) (Arr::get($payload, 'data.id') ?: Arr::get($payload, 'data.flw_ref') ?: Arr::get($payload, 'data.reference') ?: Arr::get($payload, 'data.tx_ref') ?: $reference),
                'provider_event_id' => (string) (Arr::get($payload, 'data.id') ?: Arr::get($payload, 'event') ?: $reference),
                'payment_status' => LicencePaymentStatus::Paid->value,
                'amount_allocated' => $amountPaid,
                'paid_at' => now(),
                'processed_at' => now(),
                'balance_after' => max(round((float) $payment->balance_before - $amountPaid, 2), 0),
                'raw_response_json' => $payload,
            ]);

            $fresh = $payment->fresh(['invoice', 'licence', 'declaration', 'institution']);

            $this->syncInvoiceFromPaymentAction->execute($fresh);
            $fresh = $payment->fresh(['invoice', 'licence', 'declaration', 'institution']);

            $this->logAuditAction->execute(
                null,
                'licence_payment_webhook_processed',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            event(new LicencePaymentReceived($fresh));

            return ['payment' => $fresh, 'already_processed' => false];
        });
    }
}
