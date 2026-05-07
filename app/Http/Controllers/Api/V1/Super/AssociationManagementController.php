<?php

namespace App\Http\Controllers\Api\V1\Super;

use App\Actions\Super\CreateAssociationAction;
use App\Actions\Super\DisableAssociationAction;
use App\Actions\Super\EnableAssociationAction;
use App\Actions\Super\UpdateAssociationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\DisableAssociationRequest;
use App\Http\Requests\Api\V1\StoreAssociationManagementRequest;
use App\Http\Requests\Api\V1\UpdateAssociationManagementRequest;
use App\Http\Resources\Api\V1\AssociationResource;
use App\Models\Association;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssociationManagementController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $associations = Association::query()
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->string('status')->value())
            )
            ->when(
                $request->filled('type'),
                fn ($q) => $q->where('type', $request->string('type')->value())
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['name', 'code', 'contact_email'], $search);
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Associations retrieved successfully.',
            $associations,
            AssociationResource::class
        );
    }

    public function show(Association $association): JsonResponse
    {
        return $this->success(
            'Association retrieved successfully.',
            new AssociationResource($association)
        );
    }

    public function store(
        StoreAssociationManagementRequest $request,
        CreateAssociationAction $action
    ): JsonResponse {
        $association = $action->execute(
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Association created successfully.',
            new AssociationResource($association)
        );
    }

    public function update(
        UpdateAssociationManagementRequest $request,
        Association $association,
        UpdateAssociationAction $action
    ): JsonResponse {
        $updated = $action->execute(
            $association,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Association updated successfully.',
            new AssociationResource($updated)
        );
    }

    public function activate(Request $request, Association $association, EnableAssociationAction $action): JsonResponse
    {
        $updated = $action->execute($association, $request->user(), $request->ip(), $request->userAgent());

        return $this->success('Association reactivated successfully.', new AssociationResource($updated));
    }

    public function deactivate(DisableAssociationRequest $request, Association $association, DisableAssociationAction $action): JsonResponse
    {
        $updated = $action->execute($association, $request->user(), $request->validated('reason'), $request->ip(), $request->userAgent());

        return $this->success('Association deactivated successfully.', new AssociationResource($updated));
    }

    public function destroy(Request $request, Association $association, DisableAssociationAction $action): JsonResponse
    {
        $action->execute($association, $request->user(), null, $request->ip(), $request->userAgent());

        return $this->success('Association deactivated successfully.');
    }
}
