<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\WorkReviews\FlagWorkContributorDisputeAction;
use App\Actions\WorkReviews\ReviewWorkAction;
use App\Actions\WorkReviews\ReviewWorkUpdateRequestAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\DisputeWorkContributorRequest;
use App\Http\Requests\Api\V1\ReviewWorkRequest;
use App\Http\Requests\Api\V1\ReviewWorkUpdateRequest;
use App\Http\Resources\Api\V1\WorkContributorResource;
use App\Http\Resources\Api\V1\WorkResource;
use App\Models\Work;
use App\Models\WorkContributor;
use Illuminate\Http\JsonResponse;

class AdminWorkReviewController extends BaseApiController
{
    public function review(ReviewWorkRequest $request, Work $work, ReviewWorkAction $action): JsonResponse
    {
        $this->authorize('review', $work);

        $reviewed = $action->execute($work, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return $this->success('Work reviewed successfully.', new WorkResource($reviewed));
    }

    public function reviewUpdateRequest(
        ReviewWorkUpdateRequest $request,
        Work $work,
        ReviewWorkUpdateRequestAction $action
    ): JsonResponse {
        $this->authorize('review', $work);

        $reviewed = $action->execute($work, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return $this->success('Work update request reviewed successfully.', new WorkResource($reviewed));
    }

    public function disputeContributor(DisputeWorkContributorRequest $request, Work $work, WorkContributor $contributor, FlagWorkContributorDisputeAction $action): JsonResponse
    {
        $this->authorize('review', $work);
        abort_unless($contributor->work_id === $work->id, 404);

        $updated = $action->execute($contributor, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return $this->success(
            'Work contributor flagged as disputed successfully.',
            new WorkContributorResource($updated)
        );
    }
}
