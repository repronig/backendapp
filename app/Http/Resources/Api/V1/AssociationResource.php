<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ProfileCompleteness;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AssociationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $completeness = ProfileCompleteness::make([
            'name' => $this->name,
            'code' => $this->code,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'address_line_1' => $this->address_line_1,
            'state' => $this->state?->name ?? null,
            'city' => $this->city?->name ?? null,
            'country' => $this->country,
            'description' => $this->description,
            'logo' => $this->logo_url,
        ]);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'type_label' => $this->type ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->type)) : null,
            'description' => $this->description,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'status_label' => $this->status ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->status)) : null,
            'enabled_status' => $this->is_enabled ? 'enabled' : 'disabled',
            'enabled_label' => $this->is_enabled ? 'Enabled' : 'Disabled',
            'is_enabled' => (bool) $this->is_enabled,
            'disabled_at' => optional($this->disabled_at)->toIso8601String(),
            'disable_reason' => $this->disable_reason,
            'address' => [
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'state_id' => $this->state_id,
                'city_id' => $this->city_id,
                'state_name' => $this->state?->name,
                'city_name' => $this->city?->name,
            ],
            'location' => [
                'state_id' => $this->state_id,
                'city_id' => $this->city_id,
                'state_name' => $this->state?->name,
                'city_name' => $this->city?->name,
            ],
            'state' => $this->whenLoaded('state', fn () => new StateResource($this->resource->getRelation('state'))),
            'city' => $this->whenLoaded('city', fn () => new CityResource($this->resource->getRelation('city'))),
            'profile_completeness' => $completeness,
            'logo_url' => $this->logo_url,
            'logo_thumb_url' => $this->logo_thumb_url,
            'logo_medium_url' => $this->logo_medium_url,
        ];
    }
}
