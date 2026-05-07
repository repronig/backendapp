<?php

namespace App\Actions\Access;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use RuntimeException;

class RequestPasswordResetAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        string $email,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $user = User::query()->where('email', $email)->first();

        $status = Password::broker()->sendResetLink([
            'email' => $email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new RuntimeException(__($status));
        }

        if ($user) {
            $this->logAuditAction->execute(
                $user,
                'password_reset_link_requested',
                $user,
                null,
                [
                    'email' => $user->email,
                    'requested_at' => now()->toDateTimeString(),
                ],
                $ipAddress,
                $userAgent
            );
        }
    }
}