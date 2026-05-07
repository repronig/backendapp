<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Licensing\CreateLicensingFeePlanAction;
use App\Actions\Licensing\UpdateLicensingFeePlanAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\CreateLicensingFeePlanRequest;
use App\Http\Requests\Api\V1\UpdateLicensingFeePlanRequest;
use App\Http\Resources\Api\V1\LicensingFeePlanResource;
use App\Models\LicensingFeePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLicensingFeePlanController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $plans = LicensingFeePlan::query()
            ->when($request->filled('institution_type'), fn ($q) => $q->where('institution_type', $request->string('institution_type')->value()))
            ->latest('effective_from_year')
            ->paginate($this->perPage($request));

        return $this->paginated('Licensing fee plans retrieved successfully.', $plans, LicensingFeePlanResource::class);
    }

    public function store(CreateLicensingFeePlanRequest $request, CreateLicensingFeePlanAction $action): JsonResponse
    {
        $plan = $action->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return $this->created('Licensing fee plan created successfully.', new LicensingFeePlanResource($plan));
    }

    public function update(UpdateLicensingFeePlanRequest $request, LicensingFeePlan $licensingFeePlan, UpdateLicensingFeePlanAction $action): JsonResponse
    {
        $updated = $action->execute($licensingFeePlan, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return $this->success('Licensing fee plan updated successfully.', new LicensingFeePlanResource($updated));
    }
}
