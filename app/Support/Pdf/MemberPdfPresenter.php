<?php

namespace App\Support\Pdf;

use App\Models\MemberApplication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MemberPdfPresenter
{
    use ResolvesPdfPlatformName;

    /**
     * @return array<string, mixed>
     */
    public function memberMandateCertificateData(MemberApplication $application): array
    {
        $application->loadMissing(['user', 'association']);

        $ref = (string) ($application->application_reference ?: $application->id);
        $slugRef = Str::upper(Str::slug($ref, '-')) ?: (string) $application->id;

        $applicantType = $this->headline($application->applicant_type);
        $authorType = $this->headline($application->member_author_type);
        $authorCategory = $this->headline($application->member_author_category);

        $consentDate = $application->consent_date
            ? Carbon::parse($application->consent_date)->toFormattedDateString()
            : '—';
        $consentLine = ($application->consent_accepted ? 'Yes' : 'No');
        if ($application->consent_accepted && $consentDate !== '—') {
            $consentLine .= ' — '.$consentDate;
        }

        return [
            'brandLogoHtml' => PdfAssets::brandLogoBlock(200, 6),
            'watermarkDataUri' => PdfAssets::brandLogoDataUri(),
            'certificateSealHtml' => PdfAssets::certificateSealImageHtml(96),
            'executiveDirectorSignatureHtml' => PdfAssets::executiveDirectorSignatureImageHtml(52, 220),
            'generatedAt' => now()->toFormattedDateString(),
            'platformName' => $this->pdfPlatformDisplayName(),
            'documentTitle' => 'Member mandate',
            'applicantName' => (string) ($application->user?->name ?? $application->user?->email ?? 'Member'),
            'associationLine' => (string) ($application->association?->name ?? '—'),
            'ribbonLine1' => 'This document confirms your approved membership application, your data-protection consent, and the mandate declarations held by',
            'ribbonLine2' => 'Reproduction Rights Organisation of Nigeria (REPRONIG).',
            'applicationReference' => (string) ($application->application_reference ?? 'MA-'.$application->id),
            'applicantTypeLabel' => $applicantType,
            'authorTypeLabel' => $application->applicant_type === 'author' ? $authorType : '—',
            'authorCategoryLabel' => $application->applicant_type === 'author' ? $authorCategory : '—',
            'nationality' => $application->nationality ? $this->headline($application->nationality) : '—',
            'countryOfResidence' => $application->country_of_residence ? $this->headline($application->country_of_residence) : '—',
            'affiliationStatusLabel' => $this->headline($application->affiliation_status),
            'applicationStatusLabel' => $this->headline($application->application_status),
            'consentRecordedLine' => $consentLine,
            'submittedOn' => $this->fmtDate($application->submitted_at),
            'reviewedOn' => $this->fmtDate($application->reviewed_at),
            'referenceCode' => 'MM-'.$application->id.'-'.$slugRef,
        ];
    }

    private function headline(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return Str::headline(str_replace(['_', '-'], ' ', $value));
    }

    private function fmtDate(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        $carbon = $value instanceof Carbon ? $value : Carbon::parse($value);

        return $carbon->toFormattedDateString();
    }
}
