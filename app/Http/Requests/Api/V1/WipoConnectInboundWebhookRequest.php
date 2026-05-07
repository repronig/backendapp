<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\IntegrationEnvironment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WipoConnectInboundWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::ruleset();
    }

    /**
     * @return array<string, mixed>
     */
    public static function ruleset(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:190'],
            'environment' => ['required', 'string', Rule::enum(IntegrationEnvironment::class)],
            'outbox_id' => ['sometimes', 'integer', 'min:1', 'exists:integration_outbox,id'],
            'event' => ['required', 'string', Rule::in(['succeeded', 'failed', 'acknowledged'])],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
