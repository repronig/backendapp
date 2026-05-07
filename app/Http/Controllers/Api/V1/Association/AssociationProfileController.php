<?php

namespace App\Http\Controllers\Api\V1\Association;

use App\Actions\Associations\ResolveAssociationForUserAction;
use App\Actions\Associations\UpdateAssociationProfileAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\UpdateAssociationProfileRequest;
use App\Http\Resources\Api\V1\AssociationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssociationProfileController extends BaseApiController
{
    public function show(Request $request, ResolveAssociationForUserAction $resolver): JsonResponse
    {
        $association = $resolver->execute($request->user());

        return $this->success(
            'Association profile retrieved successfully.',
            new AssociationResource($association)
        );
    }

    public function update(
        UpdateAssociationProfileRequest $request,
        ResolveAssociationForUserAction $resolver,
        UpdateAssociationProfileAction $action
    ): JsonResponse {
        $association = $resolver->execute($request->user());

        $this->authorize('update', $association);

        $updated = $action->execute(
            $association,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Association profile updated successfully.',
            new AssociationResource($updated)
        );
    }
}
