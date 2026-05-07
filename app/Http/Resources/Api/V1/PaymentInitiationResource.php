<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentInitiationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'payment_id' => $this->id,
            'payment_reference' => $this->payment_reference,
            'gateway_name' => $this->gateway_name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'invoice_id' => $this->invoice_id,
            'authorization_url' => data_get($this->raw_response_json, 'authorization_url'),
            'access_code' => data_get($this->raw_response_json, 'access_code'),
        ];
    }
}
