<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketPortalContext;
use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SupportTicket::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'portal_context' => ['required', Rule::enum(SupportTicketPortalContext::class)],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'category' => ['required', Rule::enum(SupportTicketCategory::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $portal = $this->string('portal_context')->value();
            $user = $this->user();
            if (! $user) {
                return;
            }

            $requiredRole = match ($portal) {
                SupportTicketPortalContext::Member->value => 'member',
                SupportTicketPortalContext::Association->value => 'association_officer',
                SupportTicketPortalContext::Institution->value => 'institution_user',
                default => null,
            };

            if ($requiredRole && ! $user->hasRole($requiredRole)) {
                $validator->errors()->add('portal_context', 'You do not have access for this portal context.');
            }
        });
    }
}
