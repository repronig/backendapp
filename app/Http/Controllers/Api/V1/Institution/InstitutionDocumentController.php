<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Actions\Institutions\UploadInstitutionDocumentAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreInstitutionDocumentRequest;
use App\Http\Resources\Api\V1\InstitutionDocumentResource;
use Illuminate\Http\JsonResponse;

class InstitutionDocumentController extends BaseApiController
{
    public function store(
        StoreInstitutionDocumentRequest $request,
        UploadInstitutionDocumentAction $action,
        ResolveInstitutionForUserAction $resolver
    ): JsonResponse {
        $institution = $resolver->execute($request->user());
        $this->authorize('uploadDocument', $institution);

        $document = $action->execute(
            $institution,
            $request->file('file'),
            $request->string('document_type')->value(),
            $request->user(),
            'public',
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Institution document uploaded successfully.',
            new InstitutionDocumentResource($document)
        );
    }
}