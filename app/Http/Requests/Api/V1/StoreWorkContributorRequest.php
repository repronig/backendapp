<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkContributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('member') ?? false;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'contributor_name' => ['required', 'string', 'max:255'],
            'contributor_role' => ['required', 'string', 'max:100'],
            'right_type' => ['required', 'in:exclusive,non_exclusive'],
            'ownership_percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'territory_scope' => ['nullable', 'string', 'max:255'],
        ];
    }
}
