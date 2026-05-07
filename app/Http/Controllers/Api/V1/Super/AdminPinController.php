<?php

namespace App\Http\Controllers\Api\V1\Super;

use App\Actions\Super\UpdateAdminPinAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\UpdateAdminPinRequest;
use Illuminate\Http\JsonResponse;

class AdminPinController extends BaseApiController
{
    public function update(UpdateAdminPinRequest $request, UpdateAdminPinAction $action): JsonResponse
    {
        $result = $action->execute(
            (string) $request->validated('admin_pin'),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success('Admin PIN updated successfully.', $result);
    }
}
