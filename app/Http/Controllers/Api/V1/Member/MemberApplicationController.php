<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\MemberOnboarding\CreateMemberApplicationAction;
use App\Actions\MemberOnboarding\SubmitMemberApplicationAction;
use App\Actions\MemberOnboarding\UpdateMemberApplicationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreMemberApplicationRequest;
use App\Http\Requests\Api\V1\UpdateMemberApplicationRequest;
use App\Http\Resources\Api\V1\MemberApplicationResource;
use App\Models\MemberApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberApplicationController extends BaseApiController
{
    public function store(
        StoreMemberApplicationRequest $request,
        CreateMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('create', MemberApplication::class);
        $application = $action->execute(
            $request->user(),
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Member application created successfully.',
            new MemberApplicationResource($application)
        );
    }

    public function myApplication(Request $request): JsonResponse
    {
        $application = $request->user()->memberApplication?->load([
            'user',
            'association',
            'documents',
        ]);

        return $this->success(
            'Member application retrieved successfully.',
            $application ? new MemberApplicationResource($application) : null
        );
    }

    public function show(MemberApplication $memberApplication): JsonResponse
    {
        $this->authorize('view', $memberApplication);

        return $this->success(
            'Member application retrieved successfully.',
            new MemberApplicationResource(
                $memberApplication->load(['user', 'association', 'documents'])
            )
        );
    }

    public function update(
        UpdateMemberApplicationRequest $request,
        MemberApplication $memberApplication,
        UpdateMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('update', $memberApplication);

        $application = $action->execute(
            $memberApplication,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Member application updated successfully.',
            new MemberApplicationResource($application)
        );
    }

    public function submit(
        Request $request,
        MemberApplication $memberApplication,
        SubmitMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('submit', $memberApplication);

        $application = $action->execute(
            $memberApplication,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Member application submitted successfully.',
            new MemberApplicationResource($application)
        );
    }
}