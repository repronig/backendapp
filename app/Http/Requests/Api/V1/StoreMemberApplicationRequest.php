<?php

namespace App\Http\Requests\Api\V1;

use App\Models\MemberApplication;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberApplicationRequest extends FormRequest
{
    use MemberApplicationFieldRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', MemberApplication::class) ?? false;
    }

    public function rules(): array
    {
        return $this->memberApplicationFieldRules('required');
    }
}
