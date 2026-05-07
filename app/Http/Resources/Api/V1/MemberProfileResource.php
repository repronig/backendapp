<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ProfileCompleteness;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->profile;
        $completeness = ProfileCompleteness::make([
            'full_name' => $this->user?->name,
            'email' => $this->user?->email,
            'phone' => $this->user?->phone,
            'date_of_birth' => $profile?->date_of_birth,
            'occupation' => $profile?->occupation,
            'address_line_1' => $profile?->residential_address_line_1,
            'city' => $profile?->city,
            'state' => $profile?->state,
            'country' => $profile?->country,
            'association' => $this->association?->name,
        ]);

        return [
            'member_id' => $this->id,
            'member_code' => $this->member_code,
            'member_type' => $this->member_type,
            'member_provided_id' => $this->member_provided_id,
            'approval_status' => $this->approval_status,
            'user' => $this->user ? new UserResource($this->user) : null,
            'association' => new AssociationResource($this->whenLoaded('association')),
            'profile' => [
                'date_of_birth' => $this->profile?->date_of_birth,
                'occupation' => $this->profile?->occupation,
                'residential_address_line_1' => $this->profile?->residential_address_line_1,
                'residential_address_line_2' => $this->profile?->residential_address_line_2,
                'city' => $this->profile?->city,
                'state' => $this->profile?->state,
                'country' => $this->profile?->country,
                'postal_code' => $this->profile?->postal_code,
                'publisher_name' => $this->profile?->publisher_name,
                'corporate_name' => $this->profile?->corporate_name,
            ],
            'profile_completeness' => $completeness,
            'joined_at' => $this->joined_at,
            'activated_at' => $this->activated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
