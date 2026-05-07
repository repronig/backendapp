<?php

namespace App\Http\Controllers\Api\V1\Super;

use App\Actions\Super\CreateManagedUserAction;
use App\Actions\Super\SetManagedUserStatusAction;
use App\Actions\Super\UpdateManagedUserAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\InstitutionStatusReasonRequest;
use App\Http\Requests\Api\V1\StoreSuperUserRequest;
use App\Http\Requests\Api\V1\UpdateSuperUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with([
                'roles',
                'associations',
                'member.association',
                'institutionUsers.institution',
            ])
            ->when(
                $request->filled('account_type'),
                fn ($q) => $q->where('account_type', $request->string('account_type')->value())
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->string('status')->value())
            )
            ->when($request->filled('role'), function ($q) use ($request) {
                $role = $request->string('role')->value();

                $q->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $role));
            })
            ->when($request->filled('association_id'), function ($q) use ($request) {
                $associationId = (int) $request->integer('association_id');

                $q->where(function ($sub) use ($associationId) {
                    $sub->whereHas('associations', fn ($assocQuery) => $assocQuery->where('associations.id', $associationId))
                        ->orWhereHas('member', fn ($memberQuery) => $memberQuery->where('association_id', $associationId));
                });
            })
            ->when($request->filled('institution_id'), function ($q) use ($request) {
                $institutionId = (int) $request->integer('institution_id');

                $q->whereHas('institutionUsers', fn ($instQuery) => $instQuery->where('institution_id', $institutionId));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['first_name', 'last_name', 'email', 'phone'], $search);
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Users retrieved successfully.',
            $users,
            UserResource::class
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'roles',
            'associations',
            'member.association',
            'institutionUsers.institution',
        ]);

        return $this->success(
            'User retrieved successfully.',
            new UserResource($user)
        );
    }

    public function store(
        StoreSuperUserRequest $request,
        CreateManagedUserAction $action
    ): JsonResponse {
        $user = $action->execute(
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'User created successfully.',
            new UserResource($user)
        );
    }

    public function update(
        UpdateSuperUserRequest $request,
        User $user,
        UpdateManagedUserAction $action
    ): JsonResponse {
        $updated = $action->execute(
            $user,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'User updated successfully.',
            new UserResource($updated)
        );
    }

    public function activate(
        InstitutionStatusReasonRequest $request,
        User $user,
        SetManagedUserStatusAction $action
    ): JsonResponse {
        $fresh = $action->execute(
            $user,
            'active',
            $request->user(),
            $request->validated('reason'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'User activated successfully.',
            new UserResource($fresh)
        );
    }

    public function deactivate(
        InstitutionStatusReasonRequest $request,
        User $user,
        SetManagedUserStatusAction $action
    ): JsonResponse {
        $fresh = $action->execute(
            $user,
            'inactive',
            $request->user(),
            $request->validated('reason'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'User deactivated successfully.',
            new UserResource($fresh)
        );
    }
}
