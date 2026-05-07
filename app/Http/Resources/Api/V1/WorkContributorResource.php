<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class WorkContributorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'contributor_name' => $this->contributor_name,
            'contributor_role' => $this->contributor_role,
            'contributor_role_label' => $this->contributor_role ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->contributor_role)) : null,
            'right_type' => $this->right_type,
            'right_type_label' => $this->right_type ? Str::headline(str_replace(['_', '-'], ' ', (string) $this->right_type)) : null,
            'ownership_percentage' => $this->ownership_percentage,
            'territory_scope' => $this->territory_scope,
            'is_disputed' => (bool) $this->is_disputed,
            'dispute_reason_code' => $this->dispute_reason_code,
            'dispute_reason' => $this->dispute_reason,
            'disputed_by_user_id' => $this->disputed_by_user_id,
            'disputed_at' => optional($this->disputed_at)->toIso8601String(),
            'disputed_by' => $this->whenLoaded(
                'disputedBy',
                fn () => $this->disputedBy ? new UserResource($this->disputedBy) : null
            ),
        ];
    }
}
