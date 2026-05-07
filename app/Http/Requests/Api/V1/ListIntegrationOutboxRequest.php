<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListIntegrationOutboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::enum(IntegrationSyncStatus::class)],
            'provider' => ['sometimes', 'string', Rule::enum(IntegrationProvider::class)],
        ];
    }
}
