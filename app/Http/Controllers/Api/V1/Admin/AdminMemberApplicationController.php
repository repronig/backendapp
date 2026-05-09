<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\AssociationReview\AdminApproveMemberApplicationAction;
use App\Actions\AssociationReview\RejectMemberApplicationAction;
use App\Actions\AssociationReview\RequestChangesMemberApplicationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\AdminListMemberApplicationsRequest;
use App\Http\Requests\Api\V1\ApproveMemberApplicationRequest;
use App\Http\Requests\Api\V1\RejectMemberApplicationRequest;
use App\Http\Requests\Api\V1\RequestChangesMemberApplicationRequest;
use App\Http\Resources\Api\V1\MemberApplicationResource;
use App\Models\MemberApplication;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;

class AdminMemberApplicationController extends BaseApiController
{
    public function index(AdminListMemberApplicationsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $applications = MemberApplication::query()
            ->with(['user.roles', 'association', 'documents'])
            ->when(
                isset($validated['status']),
                fn ($q) => $q->where('application_status', (string) $validated['status'])
            )
            ->when(
                isset($validated['association_id']),
                fn ($q) => $q->where('association_id', (int) $validated['association_id'])
            )
            ->when(isset($validated['search']), function ($q) use ($validated) {
                $search = (string) $validated['search'];

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereColumnIlike($sub, 'applicant_type', $search);
                    $sub->orWhereHas('user', function ($userQuery) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($userQuery, ['first_name', 'last_name', 'email'], $search);
                    })
                        ->orWhereHas('association', function ($assocQuery) use ($search) {
                            PostgresSearch::whereAnyColumnIlike($assocQuery, ['name', 'code'], $search);
                        });
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Member applications retrieved successfully.',
            $applications,
            MemberApplicationResource::class
        );
    }

    public function show(MemberApplication $memberApplication): JsonResponse
    {
        return $this->success(
            'Member application retrieved successfully.',
            new MemberApplicationResource(
                $memberApplication->load(['user.roles', 'association', 'documents'])
            )
        );
    }

    public function approve(
        ApproveMemberApplicationRequest $request,
        MemberApplication $memberApplication,
        AdminApproveMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('review', $memberApplication);

        $application = $action->execute(
            $memberApplication,
            $request->user(),
            $request->validated('comment'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Member application approved successfully.',
            new MemberApplicationResource($application->load(['user.roles', 'association', 'documents']))
        );
    }

    public function reject(
        RejectMemberApplicationRequest $request,
        MemberApplication $memberApplication,
        RejectMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('review', $memberApplication);

        $application = $action->execute(
            $memberApplication,
            $request->user(),
            $request->validated('reason'),
            $request->ip(),
            $request->userAgent(),
            true
        );

        return $this->success(
            'Member application rejected successfully.',
            new MemberApplicationResource($application->load(['user.roles', 'association', 'documents']))
        );
    }

    public function requestChanges(
        RequestChangesMemberApplicationRequest $request,
        MemberApplication $memberApplication,
        RequestChangesMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('review', $memberApplication);

        $application = $action->execute(
            $memberApplication,
            $request->user(),
            $request->validated('comment'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Changes requested successfully.',
            new MemberApplicationResource($application->load(['user.roles', 'association', 'documents']))
        );
    }
}
