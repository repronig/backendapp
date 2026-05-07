<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Security\CompleteTwoFactorLoginAction;
use App\Actions\Security\ConfirmSensitiveAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\ConfirmSensitiveRequest;
use App\Http\Requests\Api\V1\VerifyTwoFactorLoginRequest;
use App\Http\Resources\Api\V1\AuthSessionResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends BaseApiController
{
    public function __construct(private readonly LogAuditAction $logAuditAction) {}

    public function verifyLogin(VerifyTwoFactorLoginRequest $request, CompleteTwoFactorLoginAction $action): JsonResponse
    {
        try {
            $result = $action->execute(
                (int) $request->validated('challenge_id'),
                (string) $request->validated('code'),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (AuthenticationException $exception) {
            return $this->error($exception->getMessage(), 401);
        }

        return $this->success('Two-factor verification successful.', new AuthSessionResource($result));
    }

    public function status(Request $request): JsonResponse
    {
        return $this->success('Two-factor status retrieved successfully.', [
            'requires_two_factor' => (bool) $request->user()->requires_two_factor,
            'two_factor_confirmed_at' => $request->user()->two_factor_confirmed_at,
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        $before = $user->toArray();

        $user->forceFill(['requires_two_factor' => true])->save();
        $freshUser = $user->fresh();

        $this->logAuditAction->execute($freshUser, 'two_factor_enabled', $freshUser, $before, $freshUser->toArray(), $request->ip(), $request->userAgent());

        return $this->success('Two-factor authentication enabled successfully.', [
            'requires_two_factor' => true,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();
        $before = $user->toArray();

        $user->forceFill(['requires_two_factor' => false, 'two_factor_confirmed_at' => null])->save();
        $freshUser = $user->fresh();

        $this->logAuditAction->execute($freshUser, 'two_factor_disabled', $freshUser, $before, $freshUser->toArray(), $request->ip(), $request->userAgent());

        return $this->success('Two-factor authentication disabled successfully.', [
            'requires_two_factor' => false,
        ]);
    }

    public function confirmSensitive(ConfirmSensitiveRequest $request, ConfirmSensitiveAction $action): JsonResponse
    {
        $result = $action->execute($request->user(), $request->validated(), $request->ip(), $request->userAgent());

        return $this->success(
            $result['confirmed']
                ? 'Sensitive action confirmation completed successfully.'
                : 'Two-factor security code sent successfully.',
            $result,
        );
    }
}
