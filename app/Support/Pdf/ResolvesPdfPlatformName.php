<?php

namespace App\Support\Pdf;

use Illuminate\Support\Facades\DB;

trait ResolvesPdfPlatformName
{
    protected function pdfPlatformDisplayName(): string
    {
        $raw = DB::table('settings')
            ->where('group', 'platform')
            ->where('key', 'app_name')
            ->value('value');

        if ($raw === null || $raw === '') {
            return (string) config('app.name', 'REPRONIG');
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
                return $decoded !== '' ? $decoded : (string) config('app.name', 'REPRONIG');
            }

            return $raw;
        }

        return (string) config('app.name', 'REPRONIG');
    }
}
