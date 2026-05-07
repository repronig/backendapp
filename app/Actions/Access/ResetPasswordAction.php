<?php

namespace App\Actions\Access;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;

class ResetPasswordAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        array $data,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $targetUser = User::query()->where('email', $data['email'])->first();
        $before = $targetUser?->toArray();

        $status = Password::broker()->reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'] ?? $data['password'],
                'token' => $data['token'],
            ],
            function (User $user, string $password) use ($before, $ipAddress, $userAgent) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                $fresh = $user->fresh();

                $this->logAuditAction->execute(
                    $user,
                    'password_reset_completed',
                    $fresh,
                    $before,
                    $fresh->toArray(),
                    $ipAddress,
                    $userAgent
                );
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new RuntimeException(__($status));
        }
    }
}
