<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Actions\Licensing\CreateInstitutionAnnualDeclarationAction;
use App\Actions\Licensing\SubmitInstitutionAnnualDeclarationAction;
use App\Actions\Licensing\UpdateInstitutionAnnualDeclarationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreInstitutionAnnualDeclarationRequest;
use App\Http\Requests\Api\V1\UpdateInstitutionAnnualDeclarationRequest;
use App\Http\Resources\Api\V1\InstitutionAnnualDeclarationResource;
use App\Models\InstitutionAnnualDeclaration;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionAnnualDeclarationController extends BaseApiController
{
    public function index(Request $request, ResolveInstitutionForUserAction $resolver): JsonResponse
    {
        $institution = $resolver->execute($request->user());
        $this->authorize('createDeclaration', $institution);

        $declarations = $institution->annualDeclarations()
            ->with(['faculties', 'licence'])
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('declaration_status', $request->string('status')->value())
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    $pattern = PostgresSearch::wrapPattern($search);
                    $sub->whereRaw('CAST(licensing_year AS TEXT) ILIKE ?', [$pattern])
                        ->orWhere('declaration_reference', 'ilike', $pattern);
                });
            })
            ->latest('licensing_year')
            ->paginate($this->perPage($request));

        return $this->paginated('Institution annual declarations retrieved successfully.', $declarations, InstitutionAnnualDeclarationResource::class);
    }

    public function show(InstitutionAnnualDeclaration $declaration): JsonResponse
    {
        $this->authorize('view', $declaration);

        return $this->success(
            'Institution annual declaration retrieved successfully.',
            new InstitutionAnnualDeclarationResource($declaration->load(['institution', 'faculties', 'licence.payments', 'invoice', 'payments'])->loadCount(['payments', 'faculties']))
        );
    }

    public function store(
        StoreInstitutionAnnualDeclarationRequest $request,
        ResolveInstitutionForUserAction $resolver,
        CreateInstitutionAnnualDeclarationAction $action
    ): JsonResponse {
        $institution = $resolver->execute($request->user());
        $this->authorize('createDeclaration', $institution);

        $declaration = $action->execute(
            $institution,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Institution annual declaration created successfully.',
            new InstitutionAnnualDeclarationResource($declaration->load(['institution', 'faculties', 'licence.payments', 'invoice', 'payments'])->loadCount(['payments', 'faculties']))
        );
    }

    public function update(
        UpdateInstitutionAnnualDeclarationRequest $request,
        InstitutionAnnualDeclaration $declaration,
        UpdateInstitutionAnnualDeclarationAction $action
    ): JsonResponse {
        $this->authorize('update', $declaration);

        $updated = $action->execute(
            $declaration,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Institution annual declaration updated successfully.',
            new InstitutionAnnualDeclarationResource($updated->load(['faculties', 'licence']))
        );
    }

    public function submit(
        Request $request,
        InstitutionAnnualDeclaration $declaration,
        SubmitInstitutionAnnualDeclarationAction $action
    ): JsonResponse {
        $this->authorize('submit', $declaration);

        $submitted = $action->execute(
            $declaration,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Institution annual declaration submitted successfully.',
            new InstitutionAnnualDeclarationResource($submitted->load(['faculties', 'licence']))
        );
    }
}
