<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\UpdateAssociationOfficerCredentialsAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\UpdateAdminAssociationOfficerRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Association;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminAssociationOfficerController extends BaseApiController
{
    public function index(Association $association): JsonResponse
    {
        $officers = User::query()
            ->where('account_type', 'association_officer')
            ->whereHas('roles', fn ($query) => $query->where('name', 'association_officer'))
            ->whereHas('associations', fn ($query) => $query->where('associations.id', $association->id))
            ->with(['roles', 'associations'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return $this->success(
            'Association officers retrieved successfully.',
            UserResource::collection($officers)
        );
    }

    public function update(
        UpdateAdminAssociationOfficerRequest $request,
        Association $association,
        User $user,
        UpdateAssociationOfficerCredentialsAction $action
    ): JsonResponse {
        $this->ensureOfficerBelongsToAssociation($association, $user);

        $updated = $action->execute(
            $user,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Association officer credentials updated successfully.',
            new UserResource($updated)
        );
    }

    protected function ensureOfficerBelongsToAssociation(Association $association, User $user): void
    {
        if ($user->account_type !== 'association_officer' || ! $user->hasRole('association_officer')) {
            throw new NotFoundHttpException('Association officer not found for this association.');
        }

        $belongs = $user->associations()
            ->where('associations.id', $association->id)
            ->exists();

        if (! $belongs) {
            throw new NotFoundHttpException('Association officer not found for this association.');
        }
    }
}
