<?php

namespace App\Support\Pdf;

use App\Models\Licence;
use App\Models\LicencePayment;
use Illuminate\Support\Carbon;

class InstitutionPdfPresenter
{
    use ResolvesPdfPlatformName;

    /**
     * @return array<string, mixed>
     */
    public function licenceCertificateData(Licence $licence): array
    {
        $institution = $licence->institution;
        $issuedAt = $licence->issued_at ?? $licence->start_date ?? $licence->created_at ?? now();

        return [
            'brandLogoHtml' => PdfAssets::brandLogoBlock(200, 6),
            'watermarkDataUri' => PdfAssets::brandLogoDataUri(),
            'certificateSealHtml' => PdfAssets::certificateSealImageHtml(96),
            'executiveDirectorSignatureHtml' => PdfAssets::executiveDirectorSignatureImageHtml(52, 220),
            'generatedAt' => now()->toFormattedDateString(),
            'platformName' => $this->pdfPlatformDisplayName(),
            'documentTitle' => 'Annual Institution Licence',
            'institutionName' => $institution?->name ?? 'Institution',
            'licenceNumber' => $licence->licence_number ?? ('LIC-'.$licence->id),
            'licenceYear' => (string) ($licence->licence_year ?? ''),
            'validFrom' => $licence->start_date ? Carbon::parse($licence->start_date)->toFormattedDateString() : '—',
            'validTo' => $licence->end_date ? Carbon::parse($licence->end_date)->toFormattedDateString() : '—',
            'issuedOn' => $issuedAt instanceof Carbon ? $issuedAt->toFormattedDateString() : Carbon::parse($issuedAt)->toFormattedDateString(),
            'statusLabel' => ucfirst(str_replace('_', ' ', (string) $licence->licence_status)),
            'referenceCode' => 'LC-'.$licence->id.'-'.($licence->licence_year ?? now()->year),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentReceiptData(LicencePayment $payment): array
    {
        $payment->loadMissing(['institution', 'invoice', 'licence']);

        $paidAt = $payment->paid_at ?? $payment->processed_at ?? $payment->updated_at;

        $currencyCode = strtoupper(trim((string) ($payment->currency ?: 'NGN')));
        $currencySymbol = $currencyCode === 'NGN' ? '₦' : $currencyCode;

        return [
            'brandLogoHtml' => PdfAssets::brandLogoBlock(190, 6),
            'watermarkDataUri' => PdfAssets::brandLogoDataUri(),
            'generatedAt' => now()->toFormattedDateString(),
            'platformName' => $this->pdfPlatformDisplayName(),
            'documentTitle' => 'Payment receipt',
            'receiptNo' => (string) ($payment->payment_reference ?: 'PAY-'.$payment->id),
            'paidOn' => $paidAt ? Carbon::parse($paidAt)->toFormattedDateString() : '—',
            'paidAtTime' => $paidAt ? Carbon::parse($paidAt)->format('H:i') : '',
            'institutionName' => $payment->institution?->name ?? '—',
            'amount' => number_format((float) ($payment->amount_allocated ?: $payment->amount), 2),
            'currencySymbol' => $currencySymbol,
            'gatewayLabel' => $payment->gateway_name ? ucfirst((string) $payment->gateway_name) : '—',
            'gatewayReference' => $payment->gateway_reference ?: '—',
            'paymentStatusLabel' => $payment->payment_status ? ucfirst(str_replace('_', ' ', (string) $payment->payment_status)) : '—',
            'invoiceNumber' => $payment->invoice?->invoice_number ? (string) $payment->invoice->invoice_number : '—',
            'licenceNumberLine' => $payment->licence?->licence_number ? (string) $payment->licence->licence_number : '—',
        ];
    }
}
