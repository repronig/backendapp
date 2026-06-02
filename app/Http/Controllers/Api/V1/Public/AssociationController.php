<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\AssociationResource;
use App\Models\Association;
use App\Support\Membership\ApplicantAssociationMap;
use App\Support\PostgresSearch;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssociationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        if ($request->filled('applicant_type')) {
            $request->validate([
                'applicant_type' => ['required', Rule::in(ApplicantAssociationMap::APPLICANT_TYPES)],
            ]);
        }

        $applicantType = $request->filled('applicant_type')
            ? $request->string('applicant_type')->value()
            : null;

        $associations = Association::query()
            ->where('status', 'active')
            ->where('is_enabled', true)
            ->when($applicantType !== null, function ($query) use ($applicantType) {
                $codes = ApplicantAssociationMap::allowedCodesFor($applicantType);

                if ($codes !== []) {
                    $query->whereIn('code', $codes);
                }
            })
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
