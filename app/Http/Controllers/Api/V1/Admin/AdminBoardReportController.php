<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Dashboard\BuildBoardKpiSummaryAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBoardReportController extends BaseApiController
{
    public function summary(Request $request, BuildBoardKpiSummaryAction $action): JsonResponse
    {
        return $this->success('Board KPI summary retrieved successfully.', $action->execute());
    }
}
