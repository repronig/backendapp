<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertExternalIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'provider' => $this->route('provider'),
            'environment' => $this->route('environment'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::enum(IntegrationProvider::class)],
            'environment' => ['required', 'string', Rule::enum(IntegrationEnvironment::class)],
            'is_enabled' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
            'webhook_secret' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
