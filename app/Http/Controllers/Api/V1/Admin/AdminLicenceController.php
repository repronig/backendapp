<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\LicenceResource;
use App\Models\Licence;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLicenceController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $licences = Licence::query()
            ->with(['institution.profile', 'declaration.faculties', 'invoice', 'payments'])
            ->when($request->filled('licence_status') || $request->filled('status'), fn ($q) => $q->where('licence_status', $request->string('licence_status', $request->string('status')->value())->value()))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')->value()))
            ->when($request->filled('licence_year'), fn ($q) => $q->where('licence_year', (int) $request->integer('licence_year')))
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', (int) $request->integer('institution_id')))
            ->when($request->filled('outstanding_only'), fn ($q) => $q->where('outstanding_amount', '>', 0))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['licence_number', 'licence_id_snapshot'], $search);
                    $sub->orWhereHas('institution', function ($instQuery) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($instQuery, ['name', 'email', 'licence_id'], $search);
                    });
                });
            });

        $this->applyDateRange($licences, $request, 'issued_at');
        $this->applySorting($licences, $request, ['issued_at', 'created_at', 'licence_year', 'licence_status', 'payment_status'], 'issued_at');

        return $this->paginated(
            'Licences retrieved successfully.',
            $licences->paginate($this->perPage($request)),
            LicenceResource::class
        );
    }

    public function show(Licence $licence): JsonResponse
    {
        return $this->success(
            'Licence retrieved successfully.',
            new LicenceResource($licence->load(['institution.profile', 'declaration.faculties', 'invoice', 'payments'])->loadCount('payments'))
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = Licence::query()
            ->with(['institution'])
            ->when($request->filled('licence_status') || $request->filled('status'), fn ($q) => $q->where('licence_status', $request->string('licence_status', $request->string('status')->value())->value()))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')->value()))
            ->when($request->filled('licence_year'), fn ($q) => $q->where('licence_year', (int) $request->integer('licence_year')))
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', (int) $request->integer('institution_id')))
            ->when($request->filled('outstanding_only'), fn ($q) => $q->where('outstanding_amount', '>', 0))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();
                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['licence_number', 'licence_id_snapshot'], $search);
                    $sub->orWhereHas('institution', function ($instQuery) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($instQuery, ['name', 'email', 'licence_id'], $search);
                    });
                });
            });

        $this->applyDateRange($rows, $request, 'issued_at');
        $this->applySorting($rows, $request, ['issued_at', 'created_at', 'licence_year', 'licence_status', 'payment_status'], 'issued_at');
        $rows = $rows->get();

        $filename = 'licences_export_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Licence number', 'Licence year', 'Institution', 'Licence status', 'Payment status', 'Amount due', 'Amount paid', 'Outstanding amount']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->licence_number,
                    $row->licence_year,
                    optional($row->institution)->name,
                    $row->licence_status,
                    $row->payment_status,
                    $row->amount_due,
                    $row->amount_paid,
                    $row->outstanding_amount,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
