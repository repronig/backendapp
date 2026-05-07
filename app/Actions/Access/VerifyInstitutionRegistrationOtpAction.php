<?php

namespace App\Actions\Access;

use App\Actions\Audit\LogAuditAction;
use App\Models\SecurityChallenge;
use App\Models\User;
use App\Services\Mail\MailService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyInstitutionRegistrationOtpAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected MailService $mailService
    ) {}

    public function execute(array $data, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $user = User::query()
            ->where('email', $data['email'])
            ->where('account_type', 'institution_user')
            ->firstOrFail();

        if ($user->email_verified_at !== null) {
            return $this->sessionPayload($user);
        }

        $challenge = SecurityChallenge::query()
            ->where('user_id', $user->id)
            ->where('purpose', 'institution_registration_otp')
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $challenge || $challenge->isExpired()) {
            throw ValidationException::withMessages(['code' => ['The OTP has expired. Please request a new code.']]);
        }

        if (! Hash::check((string) $data['code'], $challenge->code_hash)) {
            $challenge->increment('attempts');
            throw ValidationException::withMessages(['code' => ['Invalid OTP code.']]);
        }

        $before = $user->toArray();

        $challenge->forceFill(['consumed_at' => now()])->save();
        $user->forceFill(['email_verified_at' => now()])->save();

        $freshUser = $user->fresh(['roles', 'institutionUsers.institution']);

        $this->logAuditAction->execute(
            $freshUser,
            'institution_registration_otp_verified',
            $freshUser,
            $before,
            $freshUser->toArray(),
            $ipAddress,
            $userAgent
        );

        $institution = $freshUser->institutionUsers->first()?->institution;
        if ($institution) {
            $this->mailService->sendInstitutionUserWelcome($freshUser, $institution);
        }

        return $this->sessionPayload($freshUser);
    }

    protected function sessionPayload(User $user): array
    {
        $freshUser = $user->fresh()->load('roles');

        return [
            'two_factor_required' => false,
            'token' => $freshUser->createToken('api-token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $freshUser,
        ];
    }
}
