<?php

namespace App\Actions\Access;

use App\Actions\Security\StartSecurityChallengeAction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ResendInstitutionRegistrationOtpAction
{
    public function __construct(protected StartSecurityChallengeAction $startSecurityChallengeAction)
    {
    }

    public function execute(array $data): array
    {
        $user = User::query()
            ->where('email', $data['email'])
            ->where('account_type', 'institution_user')
            ->firstOrFail();

        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages(['email' => ['This institution account is already verified.']]);
        }

        return $this->startSecurityChallengeAction->execute($user, 'institution_registration_otp');
    }
}
