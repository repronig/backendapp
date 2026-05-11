<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AdminSupportTicketReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:20000'],
        ];
    }
}
