<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberApplicationRequest extends FormRequest
{
    use MemberApplicationFieldRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->memberApplicationFieldRules('sometimes');
    }
}
