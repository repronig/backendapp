<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('supportTicket');

        return $ticket && $this->user()?->can('update', $ticket);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SupportTicketStatus::class)],
        ];
    }
}
