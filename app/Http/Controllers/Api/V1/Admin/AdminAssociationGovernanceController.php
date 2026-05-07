<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Media\UploadAssociationLogoAction;
use App\Actions\Super\DisableAssociationAction;
use App\Actions\Super\EnableAssociationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\DisableAssociationRequest;
use App\Http\Requests\Api\V1\UploadAssociationLogoRequest;
use App\Http\Resources\Api\V1\AssociationResource;
use App\Models\Association;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAssociationGovernanceController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $associations = Association::query()
            ->with(['state', 'city'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->value();
                $query->where(function ($inner) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($inner, ['name', 'code', 'contact_email'], $search);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_enabled', $request->string('status')->value() === 'enabled'))
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated('Associations retrieved successfully.', $associations, AssociationResource::class);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = Association::query()
            ->with(['state', 'city'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->value();
                $query->where(function ($inner) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($inner, ['name', 'code', 'contact_email'], $search);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_enabled', $request->string('status')->value() === 'enabled'));

        $this->applyDateRange($rows, $request);
        $this->applySorting($rows, $request, ['created_at', 'name', 'status', 'is_enabled', 'code'], 'created_at');
        $rows = $rows->get();

        $filename = 'associations_export_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Association', 'Code', 'Email', 'Phone', 'Status', 'Enabled', 'State', 'City', 'Created at']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->name,
                    $row->code,
                    $row->contact_email,
                    $row->contact_phone,
                    $row->status,
                    $row->is_enabled ? 'Yes' : 'No',
                    optional($row->state)->name,
                    optional($row->city)->name,
                    optional($row->created_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function show(Association $association): JsonResponse
    {
        return $this->success('Association retrieved successfully.', new AssociationResource($association->load(['state', 'city'])));
    }

    public function disable(DisableAssociationRequest $request, Association $association, DisableAssociationAction $action): JsonResponse
    {
        $this->authorize('update', $association);
        $association = $action->execute($association, $request->user(), $request->validated('reason'), $request->ip(), $request->userAgent());

        return $this->success('Association disabled successfully.', new AssociationResource($association));
    }

    public function enable(DisableAssociationRequest $request, Association $association, EnableAssociationAction $action): JsonResponse
    {
        $this->authorize('update', $association);
        $association = $action->execute($association, $request->user(), $request->ip(), $request->userAgent());

        return $this->success('Association enabled successfully.', new AssociationResource($association));
    }

    public function uploadLogo(UploadAssociationLogoRequest $request, Association $association, UploadAssociationLogoAction $action): JsonResponse
    {
        $this->authorize('update', $association);
        $association = $action->execute($association, $request->file('logo'));

        return $this->success('Association logo uploaded successfully.', new AssociationResource($association->load(['state', 'city'])));
    }
}
