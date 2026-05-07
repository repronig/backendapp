<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\AssociationResource;
use App\Models\Association;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssociationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $associations = Association::query()
            ->where('status', 'active')
            ->where('is_enabled', true)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));

                $query->where(function ($subQuery) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($subQuery, ['name', 'code', 'contact_email'], $search);
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Associations retrieved successfully.',
            $associations,
            AssociationResource::class
        );
    }

    public function show(Association $association): JsonResponse
    {
        abort_unless(
            $association->status === 'active' && $association->is_enabled,
            404,
            'Association not found.'
        );

        return $this->success(
            'Association retrieved successfully.',
            new AssociationResource($association->loadMissing(['state', 'city']))
        );
    }
}
