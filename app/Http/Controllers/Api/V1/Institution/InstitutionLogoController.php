<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Actions\Media\RemoveInstitutionLogoAction;
use App\Actions\Media\UploadInstitutionLogoAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\UploadInstitutionLogoRequest;
use App\Http\Resources\Api\V1\InstitutionProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionLogoController extends BaseApiController
{
    public function store(
        UploadInstitutionLogoRequest $request,
        ResolveInstitutionForUserAction $resolver,
        UploadInstitutionLogoAction $action
    ): JsonResponse {
        $institution = $resolver->execute($request->user());

        $this->authorize('update', $institution);

        $institution = $action->execute($institution, $request->file('logo'));

        return $this->success(
            'Institution logo uploaded successfully.',
            new InstitutionProfileResource($institution->load(['profile', 'state', 'city', 'legacyDocuments']))
        );
    }

    public function destroy(
        Request $request,
        ResolveInstitutionForUserAction $resolver,
        RemoveInstitutionLogoAction $action,
    ): JsonResponse {
        $institution = $resolver->execute($request->user());

        $this->authorize('update', $institution);

        $institution = $action->execute(
            $institution,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Institution logo removed successfully.',
            new InstitutionProfileResource($institution->load(['profile', 'state', 'city', 'legacyDocuments']))
        );
    }
}
