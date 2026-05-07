<?php

namespace App\Actions\Security;

use App\Actions\Audit\LogAuditAction;
use App\Models\SecurityChallenge;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class CompleteTwoFactorLoginAction
{
    public function __construct(protected LogAuditAction $logAuditAction)
    {
    }

    public function execute(int $challengeId, string $code, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $challenge = SecurityChallenge::query()->with('user.roles')->findOrFail($challengeId);

        if ($challenge->purpose !== 'login_two_factor') {
            throw new AuthenticationException('Invalid security challenge.');
        }

        if (! $challenge->user || $challenge->isConsumed() || $challenge->isExpired()) {
            throw new AuthenticationException('Security challenge is no longer valid.');
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            $challenge->increment('attempts');
            throw new AuthenticationException('Invalid security code.');
        }

        $challenge->forceFill(['consumed_at' => now()])->save();

        $user = $challenge->user;
        $before = $user->toArray();
        $token = $user->createToken('api-token')->plainTextToken;

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'last_security_confirmation_at' => now(),
        ])->save();

        $freshUser = $user->fresh()->load('roles');

        $this->logAuditAction->execute(
            $freshUser,
            'user_logged_in_with_two_factor',
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
