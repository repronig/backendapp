<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\MemberOnboarding\UploadMemberApplicationDocumentAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreMemberApplicationDocumentRequest;
use App\Http\Resources\Api\V1\MemberApplicationDocumentResource;
use App\Models\MemberApplication;
use App\Models\MemberApplicationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MemberApplicationDocumentController extends BaseApiController
{
    public function store(
        StoreMemberApplicationDocumentRequest $request,
        MemberApplication $memberApplication,
        UploadMemberApplicationDocumentAction $action
    ): JsonResponse {
        $this->authorize('update', $memberApplication);

        $document = $action->execute(
            $memberApplication,
            $request->file('file'),
            $request->string('document_type')->value(),
            $request->user(),
            'public',
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Document uploaded successfully.',
            new MemberApplicationDocumentResource($document)
        );
    }

    public function destroy(
        MemberApplication $memberApplication,
        MemberApplicationDocument $document
    ): JsonResponse {
        $this->authorize('update', $memberApplication);
        $this->authorize('delete', $document);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return $this->success('Document deleted successfully.');
    }
}