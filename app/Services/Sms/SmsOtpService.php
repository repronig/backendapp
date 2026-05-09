<?php

namespace App\Services\Sms;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsOtpService
{
    public function sendOtp(User $user, string $code, string $purposeLabel, int $expiryMinutes): bool
    {
        $enabled = (bool) config('services.sms.enabled', false);
        $phone = trim((string) $user->phone);

        if (! $enabled || $phone === '') {
            return false;
        }

        $provider = (string) config('services.sms.provider', 'termii');

        return match ($provider) {
            'termii' => $this->sendViaTermii($phone, $code, $purposeLabel, $expiryMinutes),
            default => false,
        };
    }

    protected function sendViaTermii(string $phone, string $code, string $purposeLabel, int $expiryMinutes): bool
    {
        $apiKey = (string) config('services.sms.termii.api_key');
        $sender = (string) config('services.sms.termii.sender_id', 'REPRONIG');
        $baseUrl = rtrim((string) config('services.sms.termii.base_url', 'https://api.ng.termii.com'), '/');

        if ($apiKey === '') {
            return false;
        }

        $message = sprintf(
            'Your REPRONIG OTP for %s is %s. It expires in %d minutes.',
            $purposeLabel,
            $code,
            $expiryMinutes
        );

        try {
            $response = Http::timeout(10)->post($baseUrl.'/api/sms/send', [
                'to' => $phone,
                'from' => $sender,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic',
                'api_key' => $apiKey,
            ]);

            if (! $response->successful()) {
                Log::warning('otp_sms_delivery_failed', [
                    'provider' => 'termii',
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('otp_sms_delivery_exception', [
                'provider' => 'termii',
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
