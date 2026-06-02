<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesImmutableMemberApplicationIdentity;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberApplicationRequest extends FormRequest
{
    use MemberApplicationFieldRules;
    use ValidatesImmutableMemberApplicationIdentity;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->memberApplicationFieldRules('sometimes');
    }
}
