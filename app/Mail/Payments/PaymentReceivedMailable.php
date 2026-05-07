<?php

namespace App\Mail\Payments;

use App\Mail\BaseAppMailable;
use App\Models\Invoice;
use App\Models\LicencePayment;
use App\Support\Pdf\InstitutionPdfPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Attachment;

class PaymentReceivedMailable extends BaseAppMailable
{
    public function __construct(public LicencePayment $payment) {}

    protected function subjectLine(): string
    {
        return 'REPRONIG Payment Receipt';
    }

    protected function viewName(): string
    {
        return 'emails.payments.received';
    }

    protected function viewData(): array
    {
        $payment = $this->payment->fresh(['institution', 'invoice', 'licence.invoice', 'declaration']);

        return ['payment' => $payment ?? $this->payment];
    }

    public function attachments(): array
    {
        $snapshot = $this->payment->fresh(['institution', 'invoice', 'licence', 'licence.invoice', 'declaration']);
        if (! $snapshot) {
            return [];
        }

        $invoice = $snapshot->invoice ?? $snapshot->licence?->invoice;

        $receiptFilename = 'payment-receipt-'.preg_replace('/[^a-zA-Z0-9._-]+/', '-', (string) ($snapshot->payment_reference ?: 'payment-'.$snapshot->id)).'.pdf';

        $paymentId = (int) $snapshot->id;
        $withReceipt = ['institution', 'invoice', 'licence', 'licence.invoice', 'declaration'];

        $attachments = [
            Attachment::fromData(function () use ($paymentId, $withReceipt) {
                $payment = LicencePayment::query()->with($withReceipt)->find($paymentId);
                if (! $payment) {
                    throw new \RuntimeException("Licence payment {$paymentId} not found while generating receipt PDF.");
                }
                $presenter = app(InstitutionPdfPresenter::class);

                return Pdf::loadView('pdf.payment-receipt', $presenter->paymentReceiptData($payment))
                    ->setPaper('a4', 'portrait')
                    ->output();
            }, $receiptFilename)->withMime('application/pdf'),
        ];

        $isFullyPaid = $this->isInvoiceFullyPaid($snapshot, $invoice);
        if ($isFullyPaid && $snapshot->licence) {
            $licence = $snapshot->licence;
            $slug = preg_replace('/[^a-zA-Z0-9._-]+/', '-', (string) ($licence->licence_number ?: 'licence-'.$licence->id));
            $certificateFilename = 'licence-certificate-'.($slug !== '' ? $slug : 'licence-'.$licence->id).'.pdf';

            $attachments[] = Attachment::fromData(function () use ($paymentId) {
                $payment = LicencePayment::query()->with(['licence', 'licence.institution'])->find($paymentId);
                $licence = $payment?->licence;
                if (! $licence) {
                    throw new \RuntimeException("Licence missing for payment {$paymentId} certificate attachment.");
                }
                $presenter = app(InstitutionPdfPresenter::class);

                return Pdf::loadView('pdf.licence-certificate', $presenter->licenceCertificateData($licence))
                    ->setPaper('a4', 'portrait')
                    ->output();
            }, $certificateFilename)->withMime('application/pdf');
        }

        return $attachments;
    }

    /**
     * Outstanding on the synced invoice determines full payment; fall back to payment balance when no invoice row.
     */
    protected function isInvoiceFullyPaid(LicencePayment $payment, ?Invoice $invoice): bool
    {
        if ($invoice !== null) {
            return (float) $invoice->outstanding_amount <= 0;
        }

        return (float) ($payment->balance_after ?? 0) <= 0;
    }
}
