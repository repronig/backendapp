<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\Works\CreateWorkAction;
use App\Actions\Works\DeleteWorkAction;
use App\Actions\Works\RequestWorkUpdateAction;
use App\Actions\Works\SubmitWorkAction;
use App\Actions\Works\UpdateWorkAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\RequestWorkUpdateRequest;
use App\Http\Requests\Api\V1\StoreWorkRequest;
use App\Http\Requests\Api\V1\UpdateWorkRequest;
use App\Http\Resources\Api\V1\WorkResource;
use App\Models\Work;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $member = $request->user()->member;

        $works = Work::query()
            ->when(! $member, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($member, fn ($q) => $q->where('member_id', $member->id))
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status')->value();

                if ($status === 'disputed') {
                    $q->where(function ($subQuery): void {
                        $subQuery->where('is_disputed', true)
                            ->orWhereHas('contributors', fn ($contributors) => $contributors->where('is_disputed', true));
                    });

                    return;
                }

                $q->where('work_status', $status);
            })
            ->when(
                $request->filled('search'),
                fn ($q) => PostgresSearch::whereColumnIlike($q, 'title', $request->string('search')->value())
            )
            ->with(['contributors.disputedBy', 'files', 'verifier', 'lastReviewer'])
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Works retrieved successfully.',
            $works,
            WorkResource::class
        );
    }

    protected function ensureApprovedMember(Request $request): void
    {
        $member = $request->user()?->member;

        if (! $member || $member->approval_status !== 'approved') {
            throw ValidationException::withMessages([
                'member_application' => ['Your application is still under review. Once approved, you will be able to upload your works. Thank you.'],
            ]);
        }
    }

    public function store(
        StoreWorkRequest $request,
        CreateWorkAction $action
    ): JsonResponse {
        $this->ensureApprovedMember($request);

        $work = $action->execute(
            $request->user()->member,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Work created successfully.',
            new WorkResource($work)
        );
    }

    public function show(Work $work): JsonResponse
    {
        $this->authorize('view', $work);

        return $this->success(
            'Work retrieved successfully.',
            new WorkResource($work->load(['contributors.disputedBy', 'files', 'verifier', 'lastReviewer']))
        );
    }

    public function update(
        UpdateWorkRequest $request,
        Work $work,
        UpdateWorkAction $action
    ): JsonResponse {
        $this->ensureApprovedMember($request);
        $this->authorize('update', $work);

        $updated = $action->execute(
            $work,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Work updated successfully.',
            new WorkResource($updated)
        );
    }

    public function requestUpdate(
        RequestWorkUpdateRequest $request,
        Work $work,
        RequestWorkUpdateAction $action
    ): JsonResponse {
        $this->ensureApprovedMember($request);
        $this->authorize('requestUpdate', $work);

        $updated = $action->execute(
            $work,
            $request->user()->member,
            $request->validated('note'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Work update request submitted successfully.',
            new WorkResource($updated)
        );
    }

    public function submit(
        Request $request,
        Work $work,
        SubmitWorkAction $action
    ): JsonResponse {
        $this->ensureApprovedMember($request);
        $this->authorize('submit', $work);

        $submitted = $action->execute(
            $work,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Work submitted successfully.',
            new WorkResource($submitted)
        );
    }

    public function destroy(
        Request $request,
        Work $work,
        DeleteWorkAction $action
    ): JsonResponse {
        $this->ensureApprovedMember($request);
        $this->authorize('delete', $work);

        $action->execute(
            $work,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success('Draft work deleted successfully.');
    }
}
