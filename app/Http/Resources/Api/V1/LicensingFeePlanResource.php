<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class LicensingFeePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_type' => $this->institution_type,
            'institution_type_label' => $this->institution_type ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->institution_type)) : null,
            'basis_type' => $this->basis_type,
            'basis_type_label' => $this->basis_type ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->basis_type)) : null,
            'unit_cost' => $this->unit_cost,
            'flat_amount' => $this->flat_amount,
            'effective_from_year' => $this->effective_from_year,
            'effective_to_year' => $this->effective_to_year,
            'is_active' => $this->is_active,
            'active_status' => $this->is_active ? 'active' : 'inactive',
            'active_label' => $this->is_active ? 'Active' : 'Inactive',
            'description' => $this->description,
            'metadata' => $this->metadata_json,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
