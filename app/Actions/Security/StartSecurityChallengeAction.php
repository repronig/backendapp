<?php

namespace App\Actions\Security;

use App\Models\SecurityChallenge;
use App\Models\Setting;
use App\Models\User;
use App\Services\Sms\SmsOtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StartSecurityChallengeAction
{
    public function __construct(
        protected SmsOtpService $smsOtpService
    ) {}

    public function execute(User $user, string $purpose, array $metadata = []): array
    {
        $code = (string) random_int(100000, 999999);
        $expiryMinutes = (int) config('security.two_factor_code_ttl_minutes', 10);

        $challenge = SecurityChallenge::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($expiryMinutes),
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

        $channels = $this->resolveOtpChannels();
        $emailSent = false;
        $smsSent = false;

        if ($channels['email_enabled']) {
            $emailSent = $this->sendOtpEmail($user, $code, $purposeLabel, $expiryMinutes);
        }
        if ($channels['sms_enabled']) {
            $smsSent = $this->smsOtpService->sendOtp($user, $code, $purposeLabel, $expiryMinutes);
        }

        if (! $emailSent && ! $smsSent) {
            throw ValidationException::withMessages([
                'otp' => ['Unable to deliver OTP right now. Please try again in a moment.'],
            ]);
        }

        return [
            'challenge_id' => $challenge->id,
            'expires_at' => $challenge->expires_at,
            'delivery' => [
                'email_sent' => $emailSent,
                'sms_sent' => $smsSent,
            ],
        ];
    }

    protected function sendOtpEmail(User $user, string $code, string $purposeLabel, int $expiryMinutes): bool
    {
        try {
            Mail::send('emails.auth.security-code', [
                'user' => $user,
                'code' => $code,
                'expiryMinutes' => $expiryMinutes,
                'purposeLabel' => $purposeLabel,
            ], fn ($message) => $message->to($user->email, $user->name)->subject('Your REPRONIG OTP Code'));

            return true;
        } catch (\Throwable $exception) {
            Log::warning('otp_email_delivery_exception', [
                'user_id' => $user->id,
                'purpose' => $purposeLabel,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function resolveOtpChannels(): array
    {
        $security = Setting::query()
            ->where('group', Setting::GROUP_GENERAL)
            ->where('key', 'security')
            ->value('value');
        $security = is_array($security) ? $security : [];

        return [
            'email_enabled' => (bool) ($security['otp_email_enabled'] ?? true),
            'sms_enabled' => (bool) ($security['otp_sms_enabled'] ?? false),
        ];
    }
}
