<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\AdminListWorksRequest;
use App\Http\Resources\Api\V1\WorkResource;
use App\Models\Work;
use App\Support\PostgresSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminWorkController extends BaseApiController
{
    /**
     * @return array<int, string|null>
     */
    protected function mapWorkExportRow(Work $row): array
    {
        $workStatus = $row->work_status instanceof \BackedEnum ? $row->work_status->value : (string) $row->work_status;
        $verificationStatus = $row->verification_status instanceof \BackedEnum ? $row->verification_status->value : (string) $row->verification_status;

        return [
            $row->title,
            $row->reference_number ?? $row->identifier_value,
            $row->type_of_work,
            $workStatus,
            $verificationStatus,
            $row->publisher_name,
            optional(optional($row->member)->user)->name,
            optional(optional($row->member)->association)->name,
            optional($row->submitted_at)?->toDateTimeString(),
        ];
    }

    protected function resolveWorkFilters(Request $request): array
    {
        $status = $request->string('status', $request->string('work_status')->value())->value();
        $verificationStatus = $request->string('verification_status')->value();

        if (in_array($status, ['verified', 'unverified', 'pending', 'awaiting_review'], true) && blank($verificationStatus)) {
            $verificationStatus = $status;
            $status = null;
        }

        return [$status, $verificationStatus];
    }

    protected function queryWorks(AdminListWorksRequest $request, array $with): Builder
    {
        [$status, $verificationStatus] = $this->resolveWorkFilters($request);

        $works = Work::query()
            ->with($with)
            ->when(filled($status), function ($q) use ($status) {
                if ($status === 'disputed') {
                    $q->where(function ($subQuery): void {
                        $subQuery->where('is_disputed', true)
                            ->orWhereHas('contributors', fn ($contributors) => $contributors->where('is_disputed', true));
                    });

                    return;
                }

                $q->where('work_status', $status);
            })
            ->when(filled($verificationStatus), fn ($q) => $q->where('verification_status', $verificationStatus))
            ->when($request->filled('member_id'), fn ($q) => $q->where('member_id', (int) $request->integer('member_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['title', 'identifier_value', 'publisher_name', 'doi'], $search);
                });
            });

        $this->applyDateRange($works, $request, 'submitted_at');

        if ($request->filled('sort')) {
            $this->applySorting($works, $request, ['submitted_at', 'created_at', 'updated_at', 'work_status', 'verification_status', 'title'], 'submitted_at');
        } else {
            $works->orderByRaw('submitted_at DESC NULLS LAST')->orderByDesc('created_at');
        }

        return $works;
    }

    public function index(AdminListWorksRequest $request): JsonResponse
    {
        $works = $this->queryWorks($request, ['member.user.roles', 'member.association', 'contributors.disputedBy', 'files', 'verifier', 'lastReviewer'])
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Works retrieved successfully.',
            $works,
            WorkResource::class
        );
    }

    public function export(AdminListWorksRequest $request): StreamedResponse
    {
        $rows = $this->queryWorks($request, ['member.user', 'member.association'])->get();

        $filename = 'works_export_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Title', 'Reference', 'Type', 'Work status', 'Verification status', 'Publisher', 'Member', 'Association', 'Submitted at']);

            foreach ($rows as $row) {
                fputcsv($handle, $this->mapWorkExportRow($row));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function show(Work $work): JsonResponse
    {
        return $this->success(
            'Work retrieved successfully.',
            new WorkResource(
                $work->load(['member.user.roles', 'member.association', 'contributors.disputedBy', 'files', 'verifier', 'lastReviewer'])
            )
        );
    }

    public function destroy(
        Work $work,
        \App\Actions\Works\DeleteWorkAction $action,
        \Illuminate\Http\Request $request
    ): JsonResponse {
        $this->authorize('delete', $work);

        $action->execute($work, $request->user(), $request->ip(), $request->userAgent());

        return $this->success('Work deleted successfully.');
    }
}
