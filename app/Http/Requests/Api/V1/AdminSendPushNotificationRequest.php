<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminSendPushNotificationRequest extends FormRequest
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
            'audience' => ['sometimes', 'string', Rule::in(['all_members', 'member_ids'])],
            'member_ids' => ['sometimes', Rule::requiredIf(fn () => $this->input('audience') === 'member_ids'), 'array', 'min:1'],
            'member_ids.*' => ['integer', 'min:1', 'exists:members,id'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
            'deep_link' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

