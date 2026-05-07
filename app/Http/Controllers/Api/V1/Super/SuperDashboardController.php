<?php

namespace App\Http\Controllers\Api\V1\Super;

use App\Actions\Dashboard\BuildSuperDashboardSummaryAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;

class SuperDashboardController extends BaseApiController
{
    public function summary(BuildSuperDashboardSummaryAction $action): JsonResponse
    {
        return $this->success(
            'Super admin dashboard summary retrieved successfully.',
            $action->execute()
        );
    }
}
