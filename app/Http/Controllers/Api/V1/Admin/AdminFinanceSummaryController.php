<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Dashboard\BuildAdminFinanceSummaryAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;

class AdminFinanceSummaryController extends BaseApiController
{
    public function __invoke(BuildAdminFinanceSummaryAction $action): JsonResponse
    {
        return $this->success(
            'Admin finance summary retrieved successfully.',
            $action->execute()
        );
    }
}
