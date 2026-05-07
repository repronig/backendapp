<?php

namespace App\Support\Pdf;

/**
 * Brand assets for DomPDF views (no remote URLs; embed or text fallback).
 */
final class PdfAssets
{
    /**
     * Data URI for the brand PNG when {@see public_path('repronig-logo.png')} is readable; otherwise null.
     */
    public static function brandLogoDataUri(): ?string
    {
        $path = public_path('repronig-logo.png');
        if (! is_string($path) || ! is_readable($path)) {
            return null;
        }
        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }
        $mime = str_ends_with(strtolower((string) $path), '.svg') ? 'image/svg+xml' : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /**
     * Inline logo for PDFs: embedded PNG when {@see public_path('repronig-logo.png')} exists, otherwise typographic mark.
     */
    public static function brandLogoBlock(int $maxWidthPx = 200, int $marginBottomPx = 22): string
    {
        $dataUri = self::brandLogoDataUri();
        if ($dataUri !== null) {
            return '<div class="pdf-brand-logo" style="text-align:center;margin:0 0 '.(int) $marginBottomPx.'px;">'
                .'<img src="'.e($dataUri).'" alt="REPRONIG" style="width:'.(int) $maxWidthPx.'px;max-width:100%;height:auto;display:inline-block;" />'
                .'</div>';
        }

        return '<div class="pdf-brand-fallback" style="text-align:center;margin:0 0 '.(int) $marginBottomPx.'px;padding:10px 0 6px;">'
            .'<div style="font-size:22px;font-weight:800;letter-spacing:0.14em;color:#6a1025;font-family:DejaVu Sans,Helvetica,Arial,sans-serif;">REPRONIG</div>'
            .'<div style="font-size:8.5pt;color:#667085;letter-spacing:0.12em;text-transform:uppercase;margin-top:4px;">Registry &amp; collective licensing</div>'
            .'</div>';
    }

    /**
     * Executive Director signature for licence certificates when {@see public_path('repronig-executive-director-signature.png')} exists.
     */
    public static function executiveDirectorSignatureImageHtml(int $maxHeightPx = 52, int $maxWidthPx = 220): string
    {
        $path = public_path('repronig-executive-director-signature.png');
        if (! is_string($path) || ! is_readable($path)) {
            return '';
        }
        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return '';
        }
        $mime = str_ends_with(strtolower((string) $path), '.svg') ? 'image/svg+xml' : 'image/png';
        $dataUri = 'data:'.$mime.';base64,'.base64_encode($binary);

        return '<img src="'.e($dataUri).'" alt="Signature" class="pdf-ed-signature-img" style="max-height:'.(int) $maxHeightPx.'px;max-width:'.(int) $maxWidthPx.'px;width:auto;height:auto;display:block;margin:0 auto;" />';
    }

    /**
     * Wax seal graphic for the certificate footer when {@see public_path('repronig-certificate-seal.png')} exists.
     */
    public static function certificateSealImageHtml(int $maxWidthPx = 96): string
    {
        $path = public_path('repronig-certificate-seal.png');
        if (! is_string($path) || ! is_readable($path)) {
            return '';
        }
        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return '';
        }
        $mime = str_ends_with(strtolower((string) $path), '.svg') ? 'image/svg+xml' : 'image/png';
        $dataUri = 'data:'.$mime.';base64,'.base64_encode($binary);

        return '<img src="'.e($dataUri).'" alt="REPRONIG seal" class="pdf-certificate-seal-img" style="max-width:'.(int) $maxWidthPx.'px;width:100%;height:auto;display:block;margin:0 auto;" />';
    }
}
