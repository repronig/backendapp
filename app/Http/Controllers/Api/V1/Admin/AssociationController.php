<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Super\CreateAssociationAction;
use App\Actions\Super\UpdateAssociationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreAssociationManagementRequest;
use App\Http\Requests\Api\V1\UpdateAssociationManagementRequest;
use App\Http\Resources\Api\V1\AssociationResource;
use App\Models\Association;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssociationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Association::class);

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
                $search = trim((string) $request->string('search'));

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['name', 'code', 'contact_email'], $search);
                });
            });

        $this->applyDateRange($associations, $request);
        $this->applySorting($associations, $request, ['created_at', 'name', 'status', 'type'], 'created_at');

        return $this->paginated(
            'Associations retrieved successfully.',
            $associations->paginate($this->perPage($request)),
            AssociationResource::class
        );
    }

    public function show(Association $association): JsonResponse
    {
        $this->authorize('view', $association);

        return $this->success(
            'Association retrieved successfully.',
            new AssociationResource($association->loadMissing(['state', 'city']))
        );
    }

    public function store(
        StoreAssociationManagementRequest $request,
        CreateAssociationAction $action
    ): JsonResponse {
        $this->authorize('create', Association::class);

        $association = $action->execute(
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Association created successfully.',
            new AssociationResource($association->loadMissing(['state', 'city']))
        );
    }

    public function update(
        UpdateAssociationManagementRequest $request,
        Association $association,
        UpdateAssociationAction $action
    ): JsonResponse {
        $this->authorize('update', $association);

        $updated = $action->execute(
            $association,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Association updated successfully.',
            new AssociationResource($updated->loadMissing(['state', 'city']))
        );
    }

    public function destroy(Association $association): JsonResponse
    {
        $this->authorize('delete', $association);

        $association->update(['status' => 'inactive']);

        return $this->success('Association deactivated successfully.');
    }
}
