<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisteredMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => isset($this['user']) && $this['user']
                ? new UserResource($this['user'])
                : null,
            'member_application' => isset($this['member_application']) && $this['member_application']
                ? new MemberApplicationResource($this['member_application'])
                : null,
            'otp_expires_at' => $this['otp_expires_at'] ?? null,
            'otp_delivery' => $this['otp_delivery'] ?? null,
        ];
    }
}
