<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Access\BuildCurrentUserContextAction;
use App\Actions\Access\ChangeCurrentUserPasswordAction;
use App\Actions\Access\UpdateCurrentUserProfileAction;
use App\Actions\Dashboard\BuildMeDashboardSummaryAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\UpdateMeRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MeController extends BaseApiController
{
    public function show(Request $request, BuildCurrentUserContextAction $action): JsonResponse
    {
        return $this->success('Authenticated user retrieved successfully.', $action->execute($request->user()));
    }

    public function update(
        UpdateMeRequest $request,
        UpdateCurrentUserProfileAction $action
    ): JsonResponse {
        $freshUser = $action->execute(
            $request->user(),
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Profile updated successfully.',
            new UserResource($freshUser)
        );
    }

    public function changePassword(
        ChangePasswordRequest $request,
        ChangeCurrentUserPasswordAction $action
    ): JsonResponse {
        try {
            $action->execute(
                $request->user(),
                $request->validated('current_password'),
                $request->validated('new_password'),
                $request->ip(),
                $request->userAgent()
            );

            return $this->success('Password updated successfully.');
        } catch (ValidationException $e) {
            return $this->error('Password update failed.', 422, $e->errors());
        }
    }

    public function dashboardSummary(
        Request $request,
        BuildMeDashboardSummaryAction $action
    ): JsonResponse {
        return $this->success(
            'Dashboard summary retrieved successfully.',
            $action->execute($request->user())
        );
    }
}
