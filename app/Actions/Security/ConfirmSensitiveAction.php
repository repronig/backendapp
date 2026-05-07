<?php

namespace App\Actions\Security;

use App\Actions\Audit\LogAuditAction;
use App\Models\SecurityChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ConfirmSensitiveAction
{
    public function __construct(
        protected StartSecurityChallengeAction $startSecurityChallengeAction,
        protected LogAuditAction $logAuditAction,
    ) {
    }

    public function execute(User $user, array $data, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        if (! $user->hasAnyRole(['admin', 'super_admin'])) {
            throw ValidationException::withMessages(['admin_pin' => ['Only admin users can confirm protected admin actions.']]);
        }

        if (! $user->admin_pin_hash) {
            throw ValidationException::withMessages(['admin_pin' => ['Your admin PIN has not been set. Contact a super admin before continuing.']]);
        }

        if (! Hash::check((string) ($data['admin_pin'] ?? ''), $user->admin_pin_hash)) {
            throw ValidationException::withMessages(['admin_pin' => ['The provided admin PIN is incorrect.']]);
        }

        if (! $user->requires_two_factor) {
            $before = $user->toArray();
            $user->forceFill(['last_security_confirmation_at' => now()])->save();
            $freshUser = $user->fresh();
            $this->logAuditAction->execute($freshUser, 'sensitive_action_confirmed', $freshUser, $before, $freshUser->toArray(), $ipAddress, $userAgent);

            return [
                'confirmed' => true,
                'two_factor_required' => false,
                'confirmed_until' => now()->addMinutes((int) config('security.confirmation_window_minutes', 15)),
            ];
        }

        $challengeId = $data['challenge_id'] ?? null;
        $code = $data['code'] ?? null;

        if (! $challengeId || ! $code) {
            $challenge = $this->startSecurityChallengeAction->execute($user, 'sensitive_action_confirmation');

            return [
                'confirmed' => false,
                'two_factor_required' => true,
                'challenge_id' => $challenge['challenge_id'],
                'expires_at' => $challenge['expires_at'],
            ];
        }

        $challenge = SecurityChallenge::query()->findOrFail((int) $challengeId);

        if ($challenge->user_id !== $user->id || $challenge->purpose !== 'sensitive_action_confirmation') {
            throw ValidationException::withMessages(['challenge_id' => ['Invalid security challenge.']]);
        }

        if ($challenge->isConsumed() || $challenge->isExpired()) {
            throw ValidationException::withMessages(['challenge_id' => ['Security challenge is expired or already used.']]);
        }

        if (! Hash::check((string) $code, $challenge->code_hash)) {
            $challenge->increment('attempts');
            throw ValidationException::withMessages(['code' => ['Invalid security code.']]);
        }

        $challenge->forceFill(['consumed_at' => now()])->save();
        $before = $user->toArray();
        $user->forceFill(['last_security_confirmation_at' => now(), 'two_factor_confirmed_at' => now()])->save();
        $freshUser = $user->fresh();
        $this->logAuditAction->execute($freshUser, 'sensitive_action_confirmed', $freshUser, $before, $freshUser->toArray(), $ipAddress, $userAgent);

        return [
            'confirmed' => true,
            'two_factor_required' => false,
            'confirmed_until' => now()->addMinutes((int) config('security.confirmation_window_minutes', 15)),
        ];
    }
}
