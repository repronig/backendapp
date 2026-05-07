<?php

namespace App\Actions\Security;

use App\Models\SecurityChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StartSecurityChallengeAction
{
    public function execute(User $user, string $purpose, array $metadata = []): array
    {
        $code = (string) random_int(100000, 999999);

        $challenge = SecurityChallenge::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('security.two_factor_code_ttl_minutes', 10)),
            'metadata_json' => $metadata,
        ]);

        $purposeLabel = match ($purpose) {
            'login_two_factor' => 'sign-in verification',
            'sensitive_action' => 'security confirmation',
            'sensitive_action_confirmation' => 'security confirmation',
            'member_registration_otp' => 'member registration verification',
            'institution_registration_otp' => 'institution registration verification',
            default => 'verification',
        };

        Mail::send('emails.auth.security-code', [
            'user' => $user,
            'code' => $code,
            'expiryMinutes' => (int) config('security.two_factor_code_ttl_minutes', 10),
            'purposeLabel' => $purposeLabel,
        ], fn ($message) => $message->to($user->email, $user->name)->subject('Your REPRONIG OTP Code'));

        return [
            'challenge_id' => $challenge->id,
            'expires_at' => $challenge->expires_at,
        ];
    }
}
