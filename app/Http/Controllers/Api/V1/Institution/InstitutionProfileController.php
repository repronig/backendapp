<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Actions\Institutions\AcceptInstitutionLicensingTermsAction;
use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Actions\Institutions\UpdateInstitutionProfileAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\AcceptInstitutionLicensingTermsRequest;
use App\Http\Requests\Api\V1\UpdateInstitutionProfileRequest;
use App\Http\Resources\Api\V1\InstitutionProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionProfileController extends BaseApiController
{
    public function show(
        Request $request,
        ResolveInstitutionForUserAction $resolver
    ): JsonResponse {
        $institution = $resolver->execute($request->user())->load(['profile', 'legacyDocuments']);

        return $this->success(
            'Institution profile retrieved successfully.',
            new InstitutionProfileResource($institution)
        );
    }

    public function update(
        UpdateInstitutionProfileRequest $request,
        UpdateInstitutionProfileAction $action,
        ResolveInstitutionForUserAction $resolver
    ): JsonResponse {
        $institution = $resolver->execute($request->user());
        $this->authorize('update', $institution);

        $updated = $action->execute(
            $institution,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Institution profile updated successfully.',
            new InstitutionProfileResource($updated->load(['profile', 'legacyDocuments']))
        );
    }

    public function acceptLicensingTerms(
        AcceptInstitutionLicensingTermsRequest $request,
        AcceptInstitutionLicensingTermsAction $action,
        ResolveInstitutionForUserAction $resolver
    ): JsonResponse {
        $institution = $resolver->execute($request->user());
        $this->authorize('acceptLicensingTerms', $institution);

        $fresh = $action->execute(
            $institution,
            (string) $request->validated('terms_version'),
            (string) $request->validated('acknowledged_on'),
        );

        return $this->success(
            'Institution licensing terms accepted successfully.',
            new InstitutionProfileResource($fresh)
        );
    }
}
