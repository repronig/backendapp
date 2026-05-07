<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $association = null;

        if ($this->relationLoaded('associations') && $this->associations->isNotEmpty()) {
            $association = $this->associations
                ->first(function ($association) {
                    return (bool) ($association->pivot?->is_active)
                        && $association->status === 'active'
                        && (bool) $association->is_enabled;
                });
        }

        $roles = $this->getRoleNames()->values()->all();

        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'nationality' => $this->nationality,
            'account_type' => $this->account_type,
            'status' => $this->status,
            'email_verified_at' => optional($this->email_verified_at)->toIso8601String(),
            'roles' => $roles,
            'primary_role' => $roles[0] ?? null,
            'requires_two_factor' => (bool) $this->requires_two_factor,
            'two_factor_confirmed_at' => optional($this->two_factor_confirmed_at)->toIso8601String(),
            'last_security_confirmation_at' => optional($this->last_security_confirmation_at)->toIso8601String(),
            'last_login_at' => optional($this->last_login_at)->toIso8601String(),
            'avatar_url' => $this->avatar_url,
            'avatar_thumb_url' => $this->avatar_thumb_url,
            'avatar_medium_url' => $this->avatar_medium_url,
            'primary_association' => $association ? [
                'id' => $association->id,
                'external_id' => $association->external_id,
                'name' => $association->name,
                'code' => $association->code,
                'designation_title' => $association->pivot?->designation_title,
            ] : null,
        ];
    }
}
