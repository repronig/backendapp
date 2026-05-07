<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\IntegrationEnvironment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnqueueWipoConnectOutboxRequest extends FormRequest
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
        return [
            'operation' => [
                'sometimes',
                'string',
                'max:120',
                Rule::in(['sync_work', 'sync_institution', 'sync_member', 'sync_licence']),
            ],
            'environment' => ['nullable', 'string', Rule::enum(IntegrationEnvironment::class)],
            'payload' => ['nullable', 'array'],
        ];
    }
}
