<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\TermsAndConditionResource;
use App\Models\TermsAndCondition;
use App\Support\Payments\PaymentGatewaySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformContentController extends BaseApiController
{
    public function terms(Request $request): JsonResponse
    {
        $audience = $request->string('audience', 'all')->value();
        $terms = TermsAndCondition::query()
            ->where('is_active', true)
            ->whereIn('audience', ['all', $audience])
            ->latest('published_at')
            ->first();

        return $this->success('Terms and conditions retrieved successfully.', $terms ? new TermsAndConditionResource($terms) : null);
    }

    public function settings(PaymentGatewaySettings $paymentGatewaySettings): JsonResponse
    {
        $licensing = $paymentGatewaySettings->licensing();
        $terms = $licensing['institution_licensing_terms'] ?? [];
        $bank = $licensing['repronig_bank'] ?? [];

        return $this->success('Public platform settings retrieved successfully.', [
            'licensing' => [
                'default_currency' => $licensing['default_currency'] ?? 'NGN',
                'paystack_enabled' => (bool) ($licensing['paystack_enabled'] ?? true),
                'flutterwave_enabled' => (bool) ($licensing['flutterwave_enabled'] ?? true),
                'offline_payment_enabled' => (bool) ($licensing['offline_payment_enabled'] ?? true),
                'enabled_online_gateways' => $paymentGatewaySettings->enabledOnlineGateways(),
                'default_online_gateway' => $paymentGatewaySettings->defaultOnlineGateway(),
                'repronig_bank' => [
                    'account_name' => (string) ($bank['account_name'] ?? ''),
                    'bank_name' => (string) ($bank['bank_name'] ?? ''),
                    'account_number' => (string) ($bank['account_number'] ?? ''),
                    'reference_note' => (string) ($bank['reference_note'] ?? ''),
                ],
                'institution_licensing_terms' => [
                    'version' => (string) ($terms['version'] ?? '1.0'),
                    'title' => (string) ($terms['title'] ?? ''),
                    'body' => (string) ($terms['body'] ?? ''),
                ],
            ],
        ]);
    }
}
