<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\Works\DeleteWorkFileAction;
use App\Actions\Works\UploadWorkFileAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreWorkFileRequest;
use App\Http\Resources\Api\V1\WorkFileResource;
use App\Models\Work;
use App\Models\WorkFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkFileController extends BaseApiController
{
    public function store(
        StoreWorkFileRequest $request,
        Work $work,
        UploadWorkFileAction $action
    ): JsonResponse {
        $this->authorize('update', $work);

        $file = $action->execute(
            $work,
            $request->file('file'),
            $request->string('file_type')->value(),
            $request->user(),
            'public',
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Work file uploaded successfully.',
            new WorkFileResource($file)
        );
    }

    public function destroy(
        Request $request,
        Work $work,
        WorkFile $file,
        DeleteWorkFileAction $action
    ): JsonResponse {
        $this->authorize('update', $work);
        $this->authorize('delete', $file);

        $action->execute(
            $file,
            'public',
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success('Work file deleted successfully.');
    }
}