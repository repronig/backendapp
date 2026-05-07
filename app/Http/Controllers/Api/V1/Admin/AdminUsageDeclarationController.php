<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\UsageDeclarationResource;
use App\Models\UsageDeclaration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUsageDeclarationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $declarations = UsageDeclaration::query()
            ->with(['institution', 'licence'])
            ->when(
                $request->filled('declaration_status'),
                fn ($q) => $q->where('declaration_status', $request->string('declaration_status')->value())
            )
            ->when(
                $request->filled('reporting_year'),
                fn ($q) => $q->where('reporting_year', (int) $request->integer('reporting_year'))
            )
            ->when(
                $request->filled('institution_id'),
                fn ($q) => $q->where('institution_id', (int) $request->integer('institution_id'))
            )
            ->latest('reporting_year')
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Usage declarations retrieved successfully.',
            $declarations,
            UsageDeclarationResource::class
        );
    }

    public function show(UsageDeclaration $usageDeclaration): JsonResponse
    {
        return $this->success(
            'Usage declaration retrieved successfully.',
            new UsageDeclarationResource($usageDeclaration->load(['institution', 'licence']))
        );
    }
}