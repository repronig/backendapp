<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Licensing\ApproveInstitutionAnnualDeclarationAction;
use App\Actions\Licensing\MoveInstitutionAnnualDeclarationToReviewAction;
use App\Actions\Licensing\RejectInstitutionAnnualDeclarationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\MoveInstitutionAnnualDeclarationToReviewRequest;
use App\Http\Requests\Api\V1\RejectInstitutionAnnualDeclarationRequest;
use App\Http\Resources\Api\V1\InstitutionAnnualDeclarationResource;
use App\Models\InstitutionAnnualDeclaration;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminInstitutionAnnualDeclarationController extends BaseApiController
{
    protected function declarationActionResponse(string $message, InstitutionAnnualDeclaration $declaration): JsonResponse
    {
        $fresh = $declaration->load(['institution', 'faculties', 'licence.payments', 'invoice', 'payments', 'approvedBy'])->loadCount(['payments', 'faculties']);

        return $this->success(
            $message,
            new InstitutionAnnualDeclarationResource($fresh),
            200,
            [
                'action_summary' => [
                    'status' => $fresh->declaration_status,
                    'acted_at' => $fresh->approved_at ?? $fresh->updated_at,
                    'acted_by' => $fresh->approvedBy ? Arr::only($fresh->approvedBy->toArray(), ['id', 'name', 'email']) : null,
                ],
            ]
        );
    }

    public function index(Request $request): JsonResponse
    {
        $declarations = InstitutionAnnualDeclaration::query()
            ->with(['institution', 'faculties', 'licence'])
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', (int) $request->integer('institution_id')))
            ->when($request->filled('licensing_year'), fn ($q) => $q->where('licensing_year', (int) $request->integer('licensing_year')))
            ->when($request->filled('declaration_status') || $request->filled('status'), fn ($q) => $q->where('declaration_status', $request->string('declaration_status', $request->string('status')->value())->value()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();
                $q->where(function ($sub) use ($search) {
                    $sub->orWhereHas('institution', function ($inst) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($inst, ['name', 'email', 'licence_id'], $search);
                    });
                });
            });

        $this->applyDateRange($declarations, $request, 'submitted_at');
        $this->applySorting($declarations, $request, ['submitted_at', 'created_at', 'licensing_year', 'declaration_status'], 'submitted_at');

        return $this->paginated('Institution annual declarations retrieved successfully.', $declarations->paginate($this->perPage($request)), InstitutionAnnualDeclarationResource::class);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = InstitutionAnnualDeclaration::query()
            ->with(['institution', 'licence'])
            ->withCount('faculties')
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', (int) $request->integer('institution_id')))
            ->when($request->filled('licensing_year'), fn ($q) => $q->where('licensing_year', (int) $request->integer('licensing_year')))
            ->when($request->filled('declaration_status') || $request->filled('status'), fn ($q) => $q->where('declaration_status', $request->string('declaration_status', $request->string('status')->value())->value()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();
                $q->where(function ($sub) use ($search) {
                    $sub->orWhereHas('institution', function ($inst) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($inst, ['name', 'email', 'licence_id'], $search);
                    });
                });
            });

        $this->applyDateRange($rows, $request, 'submitted_at');
        $this->applySorting($rows, $request, ['submitted_at', 'created_at', 'licensing_year', 'declaration_status'], 'submitted_at');
        $rows = $rows->get();

        $filename = 'institution_declarations_export_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Institution', 'Licensing year', 'Status', 'Declared students', 'Faculties', 'Licence number', 'Submitted at']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    optional($row->institution)->name,
                    $row->licensing_year,
                    $row->declaration_status,
                    $row->declared_student_population,
                    $row->faculties_count,
                    optional($row->licence)->licence_number,
                    optional($row->submitted_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function show(InstitutionAnnualDeclaration $declaration): JsonResponse
    {
        return $this->success(
            'Institution annual declaration retrieved successfully.',
            new InstitutionAnnualDeclarationResource($declaration->load(['institution', 'faculties', 'licence.payments', 'invoice', 'payments', 'approvedBy'])->loadCount(['payments', 'faculties']))
        );
    }

    public function moveToReview(MoveInstitutionAnnualDeclarationToReviewRequest $request, InstitutionAnnualDeclaration $declaration, MoveInstitutionAnnualDeclarationToReviewAction $action): JsonResponse
    {
        $this->authorize('approve', $declaration);

        $reviewed = $action->execute(
            $declaration->load('institution'),
            $request->user(),
            $request->validated('note'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->declarationActionResponse('Institution annual declaration moved to review successfully.', $reviewed);
    }

    public function approve(Request $request, InstitutionAnnualDeclaration $declaration, ApproveInstitutionAnnualDeclarationAction $action): JsonResponse
    {
        $this->authorize('approve', $declaration);

        $approved = $action->execute($declaration->load('institution'), $request->user(), $request->ip(), $request->userAgent());

        return $this->declarationActionResponse('Institution annual declaration approved successfully.', $approved);
    }

    public function reject(RejectInstitutionAnnualDeclarationRequest $request, InstitutionAnnualDeclaration $declaration, RejectInstitutionAnnualDeclarationAction $action): JsonResponse
    {
        $this->authorize('approve', $declaration);

        $rejected = $action->execute(
            $declaration->load('institution'),
            $request->user(),
            $request->validated('reason'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->declarationActionResponse('Institution annual declaration rejected successfully.', $rejected);
    }
}
