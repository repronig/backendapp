<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\LicencePaymentStatus;
use App\Jobs\SendOfflineInvoicePaymentSubmittedAdminNotificationsJob;
use App\Models\Invoice;
use App\Models\LicencePayment;
use App\Models\User;
use App\Support\Payments\PaymentGatewaySettings;
use App\Support\References\ReferenceCodeGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitOfflineInvoicePaymentAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected ReferenceCodeGenerator $referenceCodeGenerator,
        protected PaymentGatewaySettings $paymentGatewaySettings,
    ) {}

    public function execute(
        Invoice $invoice,
        User $actor,
        float $amount,
        bool $paidInFull,
        ?string $institutionNote,
        UploadedFile $receipt,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): LicencePayment {
        if (! $this->paymentGatewaySettings->offlinePaymentsEnabled()) {
            throw ValidationException::withMessages([
                'offline' => ['Offline invoice payments are disabled by the platform.'],
            ]);
        }

        $invoice->loadMissing('licence');

        if (! $invoice->licence) {
            throw ValidationException::withMessages(['invoice' => ['This invoice is not linked to a licence.']]);
        }

        $outstanding = (float) $invoice->outstanding_amount;

        if ($outstanding <= 0) {
            throw ValidationException::withMessages(['amount' => ['This invoice has no outstanding balance.']]);
        }

        if ($amount <= 0 || $amount > $outstanding) {
            throw ValidationException::withMessages(['amount' => ['Amount must be greater than zero and cannot exceed the invoice outstanding balance.']]);
        }

        if (LicencePayment::query()
            ->where('invoice_id', $invoice->id)
            ->where('payment_status', LicencePaymentStatus::PendingOffline->value)
            ->exists()) {
            throw ValidationException::withMessages([
                'invoice' => ['An offline payment is already awaiting review for this invoice.'],
            ]);
        }

        $licence = $invoice->licence;
        $disk = (string) config('filesystems.default', 'local');
        $path = $receipt->store('offline-payments/'.$invoice->id, $disk);
        $proofOriginalName = $receipt->getClientOriginalName();
        $proofMimeType = $receipt->getMimeType();

        return DB::transaction(function () use ($invoice, $licence, $actor, $amount, $paidInFull, $institutionNote, $path, $proofOriginalName, $proofMimeType, $ipAddress, $userAgent): LicencePayment {
            $paymentReference = $this->referenceCodeGenerator->generatePaymentReference();

            $payment = LicencePayment::create([
                'licence_id' => $licence->id,
                'institution_id' => $licence->institution_id,
                'institution_annual_declaration_id' => $licence->institution_annual_declaration_id,
                'invoice_id' => $invoice->id,
                'payment_reference' => $paymentReference,
                'gateway_name' => 'offline',
                'amount' => round($amount, 2),
                'amount_allocated' => 0,
                'balance_before' => $invoice->outstanding_amount,
                'balance_after' => $invoice->outstanding_amount,
                'currency' => $invoice->currency,
                'payment_status' => LicencePaymentStatus::PendingOffline->value,
                'raw_response_json' => [
                    'offline' => [
                        'paid_in_full' => $paidInFull,
                        'institution_note' => $institutionNote,
                        'proof_disk' => $disk,
                        'proof_disk_path' => $path,
                        'proof_original_name' => $proofOriginalName,
                        'proof_mime_type' => $proofMimeType,
                        'submitted_at' => now()->toIso8601String(),
                        'submitted_by_user_id' => $actor->id,
                    ],
                ],
            ]);

            $this->logAuditAction->execute(
                $actor,
                'offline_invoice_payment_submitted',
                $payment,
                null,
                $payment->toArray(),
                $ipAddress,
                $userAgent
            );

            SendOfflineInvoicePaymentSubmittedAdminNotificationsJob::dispatch((int) $payment->id)->afterCommit();

            return $payment->fresh(['invoice', 'licence', 'declaration', 'institution']);
        });
    }
}
