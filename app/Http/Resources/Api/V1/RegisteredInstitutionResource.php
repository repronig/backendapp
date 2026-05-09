<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisteredInstitutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => isset($this['user']) && $this['user']
                ? new UserResource($this['user'])
                : null,
            'institution' => isset($this['institution']) && $this['institution']
                ? new InstitutionProfileResource($this['institution'])
                : null,
            'otp_expires_at' => $this['otp_expires_at'] ?? null,
            'otp_delivery' => $this['otp_delivery'] ?? null,
        ];
    }
}
