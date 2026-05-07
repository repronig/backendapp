<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\Works\AddWorkContributorAction;
use App\Actions\Works\DeleteWorkContributorAction;
use App\Actions\Works\UpdateWorkContributorAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreWorkContributorRequest;
use App\Http\Requests\Api\V1\UpdateWorkContributorRequest;
use App\Http\Resources\Api\V1\WorkContributorResource;
use App\Models\Work;
use App\Models\WorkContributor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkContributorController extends BaseApiController
{
    public function store(
        StoreWorkContributorRequest $request,
        Work $work,
        AddWorkContributorAction $action
    ): JsonResponse {
        $this->authorize('update', $work);

        $contributor = $action->execute(
            $work,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Contributor added successfully.',
            new WorkContributorResource($contributor)
        );
    }

    public function update(
        UpdateWorkContributorRequest $request,
        Work $work,
        WorkContributor $contributor,
        UpdateWorkContributorAction $action
    ): JsonResponse {
        $this->authorize('update', $work);
        $this->authorize('update', $contributor);

        $updated = $action->execute(
            $contributor,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Contributor updated successfully.',
            new WorkContributorResource($updated)
        );
    }

    public function destroy(
        Request $request,
        Work $work,
        WorkContributor $contributor,
        DeleteWorkContributorAction $action
    ): JsonResponse {
        $this->authorize('update', $work);
        $this->authorize('delete', $contributor);

        $action->execute(
            $contributor,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success('Contributor deleted successfully.');
    }
}