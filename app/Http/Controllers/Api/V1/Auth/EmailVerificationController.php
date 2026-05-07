<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\Mail\MailService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends BaseApiController
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected MailService $mailService
    ) {
    }

    public function notice(Request $request): JsonResponse
    {
        return $this->success('Email verification status retrieved successfully.', [
            'email_verified' => $request->user()?->hasVerifiedEmail() ?? false,
            'email' => $request->user()?->email,
        ]);
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = $request->user();

        abort_unless($user && $user->id === $id, 403, 'Invalid verification user.');
        abort_unless(hash_equals((string) sha1($user->getEmailForVerification()), $hash), 403, 'Invalid verification hash.');

        $before = $user->toArray();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));

            $this->logAuditAction->execute(
                $user,
                'email_verified',
                $user,
                $before,
                $user->fresh()->toArray(),
                $request->ip(),
                $request->userAgent()
            );
        }

        return $this->success('Email verified successfully.', [
            'email_verified' => true,
            'email_verified_at' => $user->fresh()->email_verified_at,
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success('Email is already verified.', [
                'email_verified' => true,
                'email_verified_at' => $user->email_verified_at,
            ]);
        }

        $this->mailService->sendEmailVerification($user);

        $this->logAuditAction->execute(
            $user,
            'verification_email_resent',
            $user,
            null,
            [
                'email' => $user->email,
                'resent_at' => now()->toDateTimeString(),
            ],
            $request->ip(),
            $request->userAgent()
        );

        return $this->success('Verification email sent successfully.');
    }
}