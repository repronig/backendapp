<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'two_factor_required' => (bool) ($this['two_factor_required'] ?? false),
            'challenge_id' => $this['challenge_id'] ?? null,
            'expires_at' => $this['expires_at'] ?? null,
            'otp_delivery' => $this['otp_delivery'] ?? null,
            'token' => $this['token'] ?? null,
            'token_type' => $this['token_type'] ?? 'Bearer',
            'user' => $this->transformUser(),
        ];
    }

    protected function transformUser(): mixed
    {
        $user = $this['user'] ?? null;

        if (! $user) {
            return null;
        }

        if (is_array($user)) {
            return $user;
        }

        return new UserResource($user);
    }
}
