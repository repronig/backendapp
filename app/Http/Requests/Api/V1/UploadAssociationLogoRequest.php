<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UploadAssociationLogoRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['logo' => ['required', 'image', 'max:5120']]; }
}
