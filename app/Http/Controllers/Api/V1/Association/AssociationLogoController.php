<?php

namespace App\Http\Controllers\Api\V1\Association;

use App\Actions\Associations\ResolveAssociationForUserAction;
use App\Actions\Media\RemoveAssociationLogoAction;
use App\Actions\Media\UploadAssociationLogoAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\UploadAssociationLogoRequest;
use App\Http\Resources\Api\V1\AssociationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssociationLogoController extends BaseApiController
{
    public function store(
        UploadAssociationLogoRequest $request,
        ResolveAssociationForUserAction $resolver,
        UploadAssociationLogoAction $action
    ): JsonResponse {
        $association = $resolver->execute($request->user());

        $this->authorize('update', $association);

        $association = $action->execute($association, $request->file('logo'));

        return $this->success(
            'Association logo uploaded successfully.',
            new AssociationResource($association->load(['state', 'city']))
        );
    }

    public function destroy(
        Request $request,
        ResolveAssociationForUserAction $resolver,
        RemoveAssociationLogoAction $action
    ): JsonResponse {
        $association = $resolver->execute($request->user());

        $this->authorize('update', $association);

        $association = $action->execute(
            $association,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Association logo removed successfully.',
            new AssociationResource($association)
        );
    }
}
