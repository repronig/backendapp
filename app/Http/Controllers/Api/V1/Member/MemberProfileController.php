<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\Members\UpdateMemberProfileAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\UpdateMemberProfileRequest;
use App\Http\Resources\Api\V1\MemberProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberProfileController extends BaseApiController
{
    public function show(Request $request): JsonResponse
    {
        $member = $request->user()?->member?->load([
            'user.roles',
            'association',
            'profile',
        ]);

        return $this->success(
            'Member profile retrieved successfully.',
            $member ? new MemberProfileResource($member) : null
        );
    }

    public function update(
        UpdateMemberProfileRequest $request,
        UpdateMemberProfileAction $action
    ): JsonResponse {
        $member = $request->user()->member;

        abort_if(! $member, 403, 'Approved member profile not found.');

        $action->execute(
            $member,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        $freshMember = $member->fresh()->load([
            'user.roles',
            'association',
            'profile',
        ]);

        return $this->success(
            'Member profile updated successfully.',
            new MemberProfileResource($freshMember)
        );
    }
}