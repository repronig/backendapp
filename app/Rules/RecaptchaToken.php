<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RecaptchaToken implements ValidationRule
{
    /** Optional explicit marker from the React web app axios client. */
    public const WEB_CLIENT_HEADER = 'X-Repronig-Client';

    public const WEB_CLIENT_VALUE = 'web';

    public static function enabled(): bool
    {
        $secret = config('services.recaptcha.secret');

        return is_string($secret) && $secret !== '';
    }

    /**
     * True when the request originates from the web app (browser Origin/Referer or client header).
     * Native mobile apps do not send matching Origin headers and are exempt from reCAPTCHA.
     */
    public static function isWebClient(?Request $request = null): bool
    {
        $request ??= request();

        if ($request === null) {
            return false;
        }

        if (self::hasWebClientHeader($request)) {
            return true;
        }

        return self::hasAllowedFrontendOrigin($request);
    }

    public static function requiredForRegistration(?Request $request = null): bool
    {
        return self::enabled() && self::isWebClient($request);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::requiredForRegistration()) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Please complete the reCAPTCHA verification.');

            return;
        }

        $secret = (string) config('services.recaptcha.secret');

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (\Throwable) {
            $fail('Unable to verify reCAPTCHA. Please try again.');

            return;
        }

        if (! $response->successful()) {
            $fail('Unable to verify reCAPTCHA. Please try again.');

            return;
        }

        /** @var array<string, mixed>|null $data */
        $data = $response->json();

        if (! is_array($data) || empty($data['success'])) {
            $fail('The reCAPTCHA verification failed. Please try again.');
        }
    }

    protected static function hasWebClientHeader(Request $request): bool
    {
        $client = strtolower(trim((string) $request->header(self::WEB_CLIENT_HEADER, '')));

        return $client === self::WEB_CLIENT_VALUE;
    }

    protected static function hasAllowedFrontendOrigin(Request $request): bool
    {
        $allowed = self::allowedFrontendOrigins();

        if ($allowed === []) {
            return false;
        }

        foreach (['Origin', 'Referer'] as $header) {
            $value = trim((string) $request->header($header, ''));

            if ($value === '') {
                continue;
            }

            $origin = self::normalizeToOrigin($value);

            if ($origin !== null && in_array($origin, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected static function allowedFrontendOrigins(): array
    {
        $candidates = array_merge(
            [config('app.frontend_url')],
            (array) config('cors.allowed_origins', []),
        );

        $origins = [];

        foreach ($candidates as $url) {
            if (! is_string($url)) {
                continue;
            }

            $url = trim($url);

            if ($url === '') {
                continue;
            }

            $origin = self::normalizeToOrigin($url);

            if ($origin !== null) {
                $origins[] = $origin;
            }
        }

        return array_values(array_unique($origins));
    }

    protected static function normalizeToOrigin(string $value): ?string
    {
        $parts = parse_url($value);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }
}
