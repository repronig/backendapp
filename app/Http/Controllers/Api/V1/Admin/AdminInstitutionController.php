<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Licensing\ApproveInstitutionAction;
use App\Actions\Licensing\RejectInstitutionAction;
use App\Actions\Licensing\SetInstitutionAccountStatusAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\AdminListInstitutionsRequest;
use App\Http\Requests\Api\V1\InstitutionStatusReasonRequest;
use App\Http\Resources\Api\V1\InstitutionProfileResource;
use App\Models\Institution;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminInstitutionController extends BaseApiController
{
    protected function queryInstitutions(AdminListInstitutionsRequest $request)
    {
        $institutions = Institution::query()
            ->with(['profile', 'latestAnnualDeclaration', 'legacyDocuments'])
            ->when($request->filled('institution_type'), fn ($q) => $q->where('institution_type', $request->string('institution_type')->value()))
            ->when($request->filled('account_status') || $request->filled('status'), fn ($q) => $q->where('account_status', $request->string('account_status', $request->string('status')->value())->value()))
            ->when($request->filled('onboarding_status'), fn ($q) => $q->where('onboarding_status', $request->string('onboarding_status')->value()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();
                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['name', 'email', 'registration_number', 'licence_id', 'contact_person_name'], $search);
                });
            });

        $this->applyDateRange($institutions, $request);
        $this->applySorting($institutions, $request, ['created_at', 'name', 'account_status', 'onboarding_status'], 'created_at');

        return $institutions;
    }

    public function index(AdminListInstitutionsRequest $request): JsonResponse
    {
        $institutions = $this->queryInstitutions($request);

        return $this->paginated('Institutions retrieved successfully.', $institutions->paginate($this->perPage($request)), InstitutionProfileResource::class);
    }

    public function show(Institution $institution): JsonResponse
    {
        return $this->success(
            'Institution retrieved successfully.',
            new InstitutionProfileResource($institution->load(['profile', 'latestAnnualDeclaration.faculties', 'legacyDocuments']))
        );
    }

    public function approve(
        Request $request,
        Institution $institution,
        ApproveInstitutionAction $action
    ): JsonResponse {
        $this->authorize('approve', $institution);

        $approved = $action->execute($institution, $request->user(), $request->ip(), $request->userAgent());

        return $this->success(
            'Institution approved successfully.',
            new InstitutionProfileResource($approved->load(['profile', 'latestAnnualDeclaration.faculties', 'legacyDocuments']))
        );
    }

    public function reject(
        InstitutionStatusReasonRequest $request,
        Institution $institution,
        RejectInstitutionAction $action
    ): JsonResponse {
        $this->authorize('approve', $institution);

        $rejected = $action->execute($institution, $request->user(), $request->validated('reason'), $request->ip(), $request->userAgent());

        return $this->success(
            'Institution rejected successfully.',
            new InstitutionProfileResource($rejected->load(['profile', 'latestAnnualDeclaration.faculties', 'legacyDocuments']))
        );
    }

    public function deactivate(
        InstitutionStatusReasonRequest $request,
        Institution $institution,
        SetInstitutionAccountStatusAction $action
    ): JsonResponse {
        $this->authorize('approve', $institution);

        $updated = $action->execute($institution, 'inactive', $request->user(), $request->validated('reason'), $request->ip(), $request->userAgent());

        return $this->success(
            'Institution deactivated successfully.',
            new InstitutionProfileResource($updated->load(['profile', 'latestAnnualDeclaration.faculties', 'legacyDocuments']))
        );
    }

    public function reactivate(
        InstitutionStatusReasonRequest $request,
        Institution $institution,
        SetInstitutionAccountStatusAction $action
    ): JsonResponse {
        $this->authorize('approve', $institution);

        $updated = $action->execute($institution, 'active', $request->user(), $request->validated('reason'), $request->ip(), $request->userAgent());

        return $this->success(
            'Institution reactivated successfully.',
            new InstitutionProfileResource($updated->load(['profile', 'latestAnnualDeclaration.faculties', 'legacyDocuments']))
        );
    }

    public function export(AdminListInstitutionsRequest $request): StreamedResponse
    {
        $rows = $this->queryInstitutions($request)->get();

        $filename = 'institutions_export_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Type', 'Email', 'Licence ID', 'Account status', 'Onboarding status', 'Created at']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->name,
                    $row->institution_type,
                    $row->email,
                    $row->licence_id,
                    $row->account_status,
                    $row->onboarding_status,
                    optional($row->created_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
