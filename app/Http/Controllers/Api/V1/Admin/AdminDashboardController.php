<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Dashboard\BuildAdminDashboardSummaryAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends BaseApiController
{
    public function summary(
        BuildAdminDashboardSummaryAction $action
    ): JsonResponse {
        return $this->success(
            'Admin dashboard summary retrieved successfully.',
            $action->execute()
        );
    }
}