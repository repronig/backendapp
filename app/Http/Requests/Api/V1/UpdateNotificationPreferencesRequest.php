<?php

namespace App\Http\Requests\Api\V1;

use App\Support\Notifications\NotificationChannels;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.channel' => ['required', 'string', 'in:' . implode(',', NotificationChannels::ALL)],
            'preferences.*.notification_key' => ['nullable', 'required_without:preferences.*.event_key', 'string', 'max:100'],
            'preferences.*.event_key' => ['nullable', 'required_without:preferences.*.notification_key', 'string', 'max:100'],
            'preferences.*.is_enabled' => ['nullable', 'required_without:preferences.*.enabled', 'boolean'],
            'preferences.*.enabled' => ['nullable', 'required_without:preferences.*.is_enabled', 'boolean'],
        ];
    }
}
