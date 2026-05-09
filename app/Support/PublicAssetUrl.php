<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PublicAssetUrl
{
    /**
     * Public object keys on S3 match DB paths (e.g. "documents/..."), not "storage/documents/...".
     * Strip a mistaken "/storage/" segment from absolute URLs when it precedes a known root folder.
     */
    public static function normalizeRemotePublicObjectUrl(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || empty($parts['path'])) {
            return $url;
        }

        $path = $parts['path'];
        $prefixes = 'documents|user_avatars|work-files|works|member-application-documents|institution-documents';

        if (preg_match('#^/storage/(?:'.$prefixes.')(?:/|$)#i', $path)) {
            $parts['path'] = preg_replace('#^/storage/#i', '/', $path, 1);

            return self::rebuildHttpUrlFromParts($parts);
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private static function rebuildHttpUrlFromParts(array $parts): string
    {
        $scheme = ($parts['scheme'] ?? 'https').'://';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':'.$parts['pass'] : '';
        $auth = $user !== '' ? $user.$pass.'@' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $scheme.$auth.$host.$port.$path.$query.$fragment;
    }

    public static function fromPath(?string $path, ?string $baseUrl = null, ?Request $request = null): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['data:', 'blob:'])) {
            return $path;
        }

        $cleanPath = trim($path);

        if (Str::startsWith($cleanPath, ['http://', 'https://'])) {
            $parsedPath = parse_url($cleanPath, PHP_URL_PATH);
            $normalizedPath = is_string($parsedPath) ? ltrim($parsedPath, '/') : '';

            $isKnownPublicStorageUrl = $normalizedPath && collect(['api/v1/storage/', 'medium/storage/', 'storage/', 'public/'])
                ->contains(fn (string $prefix): bool => Str::startsWith($normalizedPath, $prefix));

            if (! $isKnownPublicStorageUrl) {
                return self::normalizeRemotePublicObjectUrl($cleanPath);
            }

            $cleanPath = $normalizedPath;
        }

        $cleanPath = ltrim($cleanPath, '/');

        foreach (['api/v1/storage/', 'medium/storage/', 'storage/', 'public/'] as $prefix) {
            if (Str::startsWith($cleanPath, $prefix)) {
                $cleanPath = Str::after($cleanPath, $prefix);
            }
        }

        if (config('filesystems.disks.public.driver') === 's3') {
            try {
                return self::normalizeRemotePublicObjectUrl(Storage::disk('public')->url($cleanPath));
            } catch (\Throwable) {
                // Fall back to the local-style URL below if the S3 disk is not fully configured yet.
            }
        }

        $storagePath = 'storage/'.ltrim($cleanPath, '/');

        $origin = $baseUrl
            ?: self::appOriginFromRequest($request)
            ?: rtrim((string) config('app.url'), '/');

        return rtrim($origin, '/').'/'.$storagePath;
    }

    public static function fromMedia(?Media $media, ?string $baseUrl = null, ?Request $request = null): ?string
    {
        if (! $media) {
            return null;
        }

        $rawUrl = null;

        if (method_exists($media, 'getUrl')) {
            $rawUrl = $media->getUrl();
        }

        if (! $rawUrl && property_exists($media, 'file_name') && property_exists($media, 'id')) {
            $rawUrl = trim($media->id.'/'.$media->file_name, '/');
        }

        return self::fromPath($rawUrl, $baseUrl, $request);
    }

    public static function appOriginFromRequest(?Request $request = null): ?string
    {
        $request = $request ?: request();

        if (! $request) {
            return null;
        }

        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();

        $origin = $scheme.'://'.$host;

        if ($port && ! in_array((int) $port, [80, 443], true)) {
            $origin .= ':'.$port;
        }

        return $origin;
    }

    public static function publicStorageUrl(?string $path, ?Request $request = null): ?string
    {
        return self::fromPath($path, null, $request);
    }
}
