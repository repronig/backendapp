<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkRequest extends FormRequest
{
    use WorkFieldRules;


    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->workFieldRules('sometimes');
    }
}
