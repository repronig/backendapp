<?php

namespace App\Http\Controllers\Api\V1\Super;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\SyncRolePermissionsRequest;
use App\Http\Resources\Api\V1\PermissionResource;
use App\Http\Resources\Api\V1\RoleResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends BaseApiController
{
    public function roles(): JsonResponse
    {
        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return $this->success(
            'Roles retrieved successfully.',
            RoleResource::collection($roles)
        );
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->get();

        return $this->success(
            'Permissions retrieved successfully.',
            PermissionResource::collection($permissions)
        );
    }

    public function showRole(Role $role): JsonResponse
    {
        $role->load('permissions');

        return $this->success(
            'Role retrieved successfully.',
            new RoleResource($role)
        );
    }

    public function syncRolePermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $role->syncPermissions($request->validated('permissions'));
        $role->load('permissions');

        return $this->success(
            'Role permissions updated successfully.',
            new RoleResource($role)
        );
    }
}
