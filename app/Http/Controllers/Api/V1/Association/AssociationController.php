<?php

namespace App\Http\Controllers\Api\V1\Association;

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
        $this->authorize('view', $association);

        return $this->success(
            'Association retrieved successfully.',
            new AssociationResource($association)
        );
    }

    public function store(StoreAssociationManagementRequest $request): JsonResponse
    {
        $this->authorize('create', Association::class);

        $association = Association::create($request->validated());

        return $this->created(
            'Association created successfully.',
            new AssociationResource($association)
        );
    }

    public function update(UpdateAssociationManagementRequest $request, Association $association): JsonResponse
    {
        $this->authorize('update', $association);

        $association->update($request->validated());

        return $this->success(
            'Association updated successfully.',
            new AssociationResource($association->fresh())
        );
    }

    public function destroy(Association $association): JsonResponse
    {
        $this->authorize('delete', $association);

        $association->update(['status' => 'inactive']);

        return $this->success(
            'Association deactivated successfully.'
        );
    }
}
