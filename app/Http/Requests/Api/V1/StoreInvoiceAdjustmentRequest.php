<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'adjustment_type' => ['required', 'string', 'in:credit_note,manual_adjustment'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason_code' => ['required', 'string', 'max:50'],
            'reason' => ['required', 'string'],
        ];
    }
}
