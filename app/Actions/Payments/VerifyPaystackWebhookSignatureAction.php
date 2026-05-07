<?php

namespace App\Actions\Payments;

use App\Exceptions\InvalidWebhookSignatureException;

class VerifyPaystackWebhookSignatureAction
{
    public function execute(string $rawPayload, ?string $signature): void
    {
        $secret = (string) config('services.paystack.secret_key');

        if ($secret === '') {
            throw new InvalidWebhookSignatureException('Paystack secret key is not configured.');
        }

        $normalizedSignature = trim((string) $signature);

        if ($normalizedSignature === '') {
            throw new InvalidWebhookSignatureException('Missing webhook signature.');
        }

        $computed = hash_hmac('sha512', $rawPayload, $secret);

        if (! hash_equals($computed, $normalizedSignature)) {
            throw new InvalidWebhookSignatureException('Invalid webhook signature.');
        }
    }
}