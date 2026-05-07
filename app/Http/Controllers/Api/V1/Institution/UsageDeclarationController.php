<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Actions\UsageDeclarations\CreateUsageDeclarationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreUsageDeclarationRequest;
use App\Http\Resources\Api\V1\UsageDeclarationResource;
use App\Models\Licence;
use App\Models\UsageDeclaration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsageDeclarationController extends BaseApiController
{
    public function index(
        Request $request,
        ResolveInstitutionForUserAction $resolver
    ): JsonResponse {
        $institution = $resolver->execute($request->user());

        $declarations = UsageDeclaration::query()
            ->where('institution_id', $institution->id)
            ->latest('reporting_year')
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Usage declarations retrieved successfully.',
            $declarations,
            UsageDeclarationResource::class
        );
    }

    public function show(UsageDeclaration $usageDeclaration): JsonResponse
    {
        $this->authorize('view', $usageDeclaration);

        return $this->success(
            'Usage declaration retrieved successfully.',
            new UsageDeclarationResource($usageDeclaration)
        );
    }

    public function store(
        StoreUsageDeclarationRequest $request,
        Licence $licence,
        CreateUsageDeclarationAction $action
    ): JsonResponse {
        $this->authorize('declareUsage', $licence);

        $declaration = $action->execute(
            $licence,
            $request->user(),
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Usage declaration submitted successfully.',
            new UsageDeclarationResource($declaration)
        );
    }
}