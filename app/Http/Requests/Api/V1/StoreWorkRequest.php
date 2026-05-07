<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkRequest extends FormRequest
{
    use WorkFieldRules;


    public function authorize(): bool
    {
        return $this->user()?->hasRole('member') ?? false;
    }

    public function rules(): array
    {
        return $this->workFieldRules('required');
    }
}
