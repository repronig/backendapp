<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class RecaptchaToken implements ValidationRule
{
    public static function enabled(): bool
    {
        $secret = config('services.recaptcha.secret');

        return is_string($secret) && $secret !== '';
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::enabled()) {
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
}
