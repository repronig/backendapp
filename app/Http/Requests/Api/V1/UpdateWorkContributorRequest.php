<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkContributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'contributor_name' => ['sometimes', 'string', 'max:255'],
            'contributor_role' => ['sometimes', 'string', 'max:100'],
            'right_type' => ['sometimes', 'in:exclusive,non_exclusive'],
            'ownership_percentage' => ['sometimes', 'numeric', 'min:0.01', 'max:100'],
            'territory_scope' => ['nullable', 'string', 'max:255'],
        ];
    }
}
