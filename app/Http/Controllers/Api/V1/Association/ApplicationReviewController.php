<?php

namespace App\Http\Controllers\Api\V1\Association;

use App\Actions\AssociationReview\ApproveMemberApplicationAction;
use App\Actions\AssociationReview\RejectMemberApplicationAction;
use App\Actions\AssociationReview\RequestChangesMemberApplicationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\ApproveMemberApplicationRequest;
use App\Http\Requests\Api\V1\RejectMemberApplicationRequest;
use App\Http\Requests\Api\V1\RequestChangesMemberApplicationRequest;
use App\Http\Resources\Api\V1\MemberApplicationResource;
use App\Models\Association;
use App\Models\MemberApplication;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApplicationReviewController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('reviewApplications', Association::class);

        $associationId = $request->user()
            ->associations()
            ->where('associations.status', 'active')
            ->where('associations.is_enabled', true)
            ->where('association_user.is_active', true)
            ->value('associations.id');

        if (! $associationId) {
            throw ValidationException::withMessages([
                'association' => ['No active enabled association link was found for this user.'],
            ]);
        }

        $applications = MemberApplication::query()
            ->with(['user', 'association', 'documents'])
            ->where('association_id', $associationId)
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('application_status', $request->string('status')->value())
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereColumnIlike($sub, 'applicant_type', $search);
                    $sub->orWhereHas('user', function ($userQuery) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($userQuery, ['first_name', 'last_name', 'email'], $search);
                    });
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Association applications retrieved successfully.',
            $applications,
            MemberApplicationResource::class
        );
    }

    public function show(MemberApplication $memberApplication): JsonResponse
    {
        $this->authorize('view', $memberApplication);

        return $this->success(
            'Association application retrieved successfully.',
            new MemberApplicationResource(
                $memberApplication->load(['user', 'association', 'documents'])
            )
        );
    }

    public function approve(
        ApproveMemberApplicationRequest $request,
        MemberApplication $memberApplication,
        ApproveMemberApplicationAction $action
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
            new MemberApplicationResource(
                $application->load(['user', 'association', 'documents'])
            )
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
            $request->userAgent()
        );

        return $this->success(
            'Member application rejected successfully.',
            new MemberApplicationResource(
                $application->load(['user', 'association', 'documents'])
            )
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
            new MemberApplicationResource(
                $application->load(['user', 'association', 'documents'])
            )
        );
    }
}
