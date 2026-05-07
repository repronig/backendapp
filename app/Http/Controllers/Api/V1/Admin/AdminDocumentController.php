<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Documents\DeleteDocumentAction;
use App\Actions\Documents\UploadDocumentAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreAdminDocumentRequest;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Association;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Member;
use App\Models\Work;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDocumentController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->with(['uploadedBy'])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')->value()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();
                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['title', 'document_type', 'category'], $search);
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated('Documents retrieved successfully.', $documents, DocumentResource::class);
    }

    public function store(StoreAdminDocumentRequest $request, UploadDocumentAction $action): JsonResponse
    {
        $validated = $request->validated();

        $target = match ($validated['target_type']) {
            'member' => Member::findOrFail($validated['target_id']),
            'institution' => Institution::findOrFail($validated['target_id']),
            'association' => Association::findOrFail($validated['target_id']),
            'work' => Work::findOrFail($validated['target_id']),
        };

        $document = $action->execute($target, $request->file('file'), $validated, $request->user(), $request->ip(), $request->userAgent());

        return $this->created('Document uploaded successfully.', new DocumentResource($document));
    }

    public function destroy(Request $request, Document $document, DeleteDocumentAction $action): JsonResponse
    {
        $this->authorize('delete', $document);
        $action->execute($document, $request->user(), $request->ip(), $request->userAgent());

        return $this->success('Document deleted successfully.');
    }
}
