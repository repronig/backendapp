<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Actions\Dashboard\BuildInstitutionDashboardAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionDashboardController extends BaseApiController
{
    public function show(
        Request $request,
        BuildInstitutionDashboardAction $action
    ): JsonResponse {
        return $this->success(
            'Institution dashboard retrieved successfully.',
            $action->execute($request->user())
        );
    }
}