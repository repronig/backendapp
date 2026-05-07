<?php

namespace App\Actions\Access;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Security\StartSecurityChallengeAction;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected StartSecurityChallengeAction $startSecurityChallengeAction,
    ) {
    }

    public function execute(
        array $credentials,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Invalid login credentials.');
        }

        if ($user->status !== 'active') {
            throw new AuthenticationException('This account is not active.');
        }

        if ($user->requires_two_factor) {
            $challenge = $this->startSecurityChallengeAction->execute($user, 'login_two_factor');

            return [
                'two_factor_required' => true,
                'challenge_id' => $challenge['challenge_id'],
                'expires_at' => $challenge['expires_at'],
                'user' => $user->only(['id', 'email', 'first_name', 'last_name']),
            ];
        }

        $before = $user->toArray();

        $token = $user->createToken('api-token')->plainTextToken;

        $user->forceFill([
            'last_security_confirmation_at' => now(),
            'last_login_at' => now(),
        ])->save();

        $freshUser = $user->fresh()->load('roles');

        $this->logAuditAction->execute(
            $freshUser,
            'user_logged_in',
            $freshUser,
            $before,
            $freshUser->toArray(),
            $ipAddress,
            $userAgent
        );

        return [
            'two_factor_required' => false,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $freshUser,
        ];
    }
}