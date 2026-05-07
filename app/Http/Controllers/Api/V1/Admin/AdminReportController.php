<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Licensing\BuildInstitutionLicensingReportAction;
use App\Actions\Reports\BuildAdminCompletenessReportAction;
use App\Actions\Reports\BuildAdminMemberReportAction;
use App\Actions\Reports\BuildAdminWorkReportAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\InstitutionLicensingReportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReportController extends BaseApiController
{
    public function members(BuildAdminMemberReportAction $action): JsonResponse
    {
        return $this->success('Member report retrieved successfully.', $action->execute());
    }

    public function works(BuildAdminWorkReportAction $action): JsonResponse
    {
        return $this->success('Work report retrieved successfully.', $action->execute());
    }

    public function licences(Request $request, BuildInstitutionLicensingReportAction $action): JsonResponse
    {
        $report = $action->execute($request->all());

        return $this->paginated('Licence report retrieved successfully.', $report, InstitutionLicensingReportResource::class);
    }

    public function completeness(BuildAdminCompletenessReportAction $action): JsonResponse
    {
        return $this->success('Completeness report retrieved successfully.', $action->execute());
    }
}
