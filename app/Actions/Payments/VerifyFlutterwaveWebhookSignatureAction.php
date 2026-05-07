<?php

namespace App\Actions\Payments;

use App\Exceptions\InvalidWebhookSignatureException;

class VerifyFlutterwaveWebhookSignatureAction
{
    public function execute(?string $signature): void
    {
        $secretHash = (string) config('services.flutterwave.secret_hash');

        if ($secretHash === '') {
            return;
        }

        $normalizedSignature = trim((string) $signature);

        if ($normalizedSignature === '') {
            throw new InvalidWebhookSignatureException('Missing Flutterwave webhook signature.');
        }

        if (! hash_equals($secretHash, $normalizedSignature)) {
            throw new InvalidWebhookSignatureException('Invalid Flutterwave webhook signature.');
        }
    }
}
