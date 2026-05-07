<?php

namespace App\Actions\Access;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerifyPasswordResetTokenAction
{
    public function execute(string $email, string $token): void
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Invalid user.'],
            ]);
        }

        $tokenExists = app('auth.password.broker')->getRepository()->exists(
            $user,
            $token
        );

        if (! $tokenExists) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired token.'],
            ]);
        }
    }
}