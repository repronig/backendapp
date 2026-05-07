<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Access\RequestPasswordResetAction;
use App\Actions\Access\ResetPasswordAction;
use App\Actions\Access\VerifyPasswordResetTokenAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Requests\Api\V1\VerifyResetTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PasswordResetController extends BaseApiController
{
    public function forgotPassword(
        ForgotPasswordRequest $request,
        RequestPasswordResetAction $action
    ): JsonResponse {
        try {
            $action->execute(
                $request->validated('email'),
                $request->ip(),
                $request->userAgent()
            );

            return $this->success('Password reset link sent successfully.');
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function resetPassword(
        ResetPasswordRequest $request,
        ResetPasswordAction $action
    ): JsonResponse {
        try {
            $action->execute(
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );

            return $this->success('Password has been reset successfully.');
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function verifyToken(
        VerifyResetTokenRequest $request,
        VerifyPasswordResetTokenAction $action
    ): JsonResponse {
        try {
            $action->execute(
                $request->validated('email'),
                $request->validated('token')
            );

            return $this->success('Token is valid.');
        } catch (ValidationException $e) {
            return $this->error('Token validation failed.', 422, $e->errors());
        }
    }
}