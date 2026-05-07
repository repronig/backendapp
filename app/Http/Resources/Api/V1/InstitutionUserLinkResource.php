<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionUserLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'institution_id' => $this->institution_id,
            'institution_name' => $this->whenLoaded('institution', fn () => $this->institution?->name),
            'role_label' => $this->role_label,
            'is_primary' => (bool) $this->is_primary,
            'is_active' => (bool) $this->is_active,
        ];
    }
}