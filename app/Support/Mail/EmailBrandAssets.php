<?php

namespace App\Support\Mail;

/**
 * Brand assets for transactional HTML mail (queue-safe, no hotlink dependency).
 */
final class EmailBrandAssets
{
    /**
     * Base URL for publicly served assets (no /api/v1 prefix). Used when the logo
     * cannot be inlined as a data URI.
     */
    public static function publicWebBaseUrl(): string
    {
        $override = rtrim((string) config('mail.asset_base_url'), '/');
        if ($override !== '') {
            return $override;
        }

        $base = rtrim((string) config('app.url'), '/');
        $stripped = (string) (preg_replace('#/api/v1/?$#i', '', $base) ?: $base);

        return rtrim($stripped, '/');
    }

    /**
     * Image src for the REPRONIG header logo: inline data URI when the PNG exists
     * on disk (works for queued mail and strict mail clients); otherwise absolute URL.
     */
    public static function logoImgSrc(): string
    {
        $path = public_path('repronig-logo.png');
        if (is_readable($path)) {
            $binary = @file_get_contents($path);
            if ($binary !== false && $binary !== '') {
                return 'data:image/png;base64,'.base64_encode($binary);
            }
        }

        $root = self::publicWebBaseUrl();

        return $root !== '' ? $root.'/repronig-logo.png' : url('repronig-logo.png');
    }
}
