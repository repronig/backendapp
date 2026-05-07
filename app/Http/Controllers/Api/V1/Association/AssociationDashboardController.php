<?php

namespace App\Http\Controllers\Api\V1\Association;

use App\Actions\Dashboard\BuildAssociationDashboardAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssociationDashboardController extends BaseApiController
{
    public function show(
        Request $request,
        BuildAssociationDashboardAction $action
    ): JsonResponse {
        return $this->success(
            'Association dashboard retrieved successfully.',
            $action->execute($request->user())
        );
    }
}