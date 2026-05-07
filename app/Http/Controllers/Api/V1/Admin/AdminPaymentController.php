<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Licensing\ConfirmOfflineLicencePaymentAction;
use App\Actions\Licensing\RejectOfflineLicencePaymentAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\ConfirmOfflinePaymentRequest;
use App\Http\Requests\Api\V1\RejectOfflinePaymentRequest;
use App\Http\Resources\Api\V1\LicencePaymentResource;
use App\Models\LicencePayment;
use App\Support\PostgresSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPaymentController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $payments = LicencePayment::query()
            ->with(['licence.declaration.faculties', 'licence.institution', 'institution', 'declaration', 'invoice', 'reconciledBy', 'reconciliations.processor']);

        $this->applyStatusFilter($payments, $request);

        $payments
            ->when($request->filled('gateway_name'), fn ($q) => $q->where('gateway_name', $request->string('gateway_name')->value()))
            ->when($request->filled('licence_id'), fn ($q) => $q->where('licence_id', (int) $request->integer('licence_id')))
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', (int) $request->integer('institution_id')))
            ->when($request->filled('licensing_year'), fn ($q) => $q->whereHas('declaration', fn ($sub) => $sub->where('licensing_year', (int) $request->integer('licensing_year'))))
            ->when($request->filled('outstanding_only'), fn ($q) => $q->where('balance_after', '>', 0))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['payment_reference', 'gateway_reference'], $search);
                    $sub->orWhereHas('institution', function ($institutionQuery) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($institutionQuery, ['name', 'licence_id'], $search);
                    });
                });
            });

        $this->applyDateRange($payments, $request, 'paid_at');
        $this->applySorting($payments, $request, ['paid_at', 'created_at', 'payment_status', 'amount'], 'paid_at');

        return $this->paginated(
            'Payments retrieved successfully.',
            $payments->paginate($this->perPage($request)),
            LicencePaymentResource::class
        );
    }

    public function show(LicencePayment $payment): JsonResponse
    {
        return $this->success(
            'Payment retrieved successfully.',
            new LicencePaymentResource($payment->load(['licence.declaration.faculties', 'licence.institution', 'institution', 'declaration', 'invoice', 'reconciledBy', 'reconciliations.processor']))
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = LicencePayment::query()->with(['institution', 'declaration']);

        $this->applyStatusFilter($rows, $request);

        $rows
            ->when($request->filled('gateway_name'), fn ($q) => $q->where('gateway_name', $request->string('gateway_name')->value()))
            ->when($request->filled('licence_id'), fn ($q) => $q->where('licence_id', (int) $request->integer('licence_id')))
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', (int) $request->integer('institution_id')))
            ->when($request->filled('licensing_year'), fn ($q) => $q->whereHas('declaration', fn ($sub) => $sub->where('licensing_year', (int) $request->integer('licensing_year'))))
            ->when($request->filled('outstanding_only'), fn ($q) => $q->where('balance_after', '>', 0))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();
                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['payment_reference', 'gateway_reference'], $search);
                    $sub->orWhereHas('institution', function ($institutionQuery) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($institutionQuery, ['name', 'licence_id'], $search);
                    });
                });
            });

        $this->applyDateRange($rows, $request, 'paid_at');
        $this->applySorting($rows, $request, ['paid_at', 'created_at', 'payment_status', 'amount'], 'paid_at');
        $rows = $rows->get();

        $filename = 'payments_export_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Payment reference', 'Gateway', 'Institution', 'Payment status', 'Amount', 'Currency', 'Paid at']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->payment_reference,
                    $row->gateway_name,
                    optional($row->institution)->name,
                    $row->payment_status,
                    $row->amount,
                    $row->currency,
                    optional($row->paid_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function confirmOffline(
        ConfirmOfflinePaymentRequest $request,
        LicencePayment $payment,
        ConfirmOfflineLicencePaymentAction $action
    ): JsonResponse {
        $fresh = $action->execute(
            $payment,
            $request->user(),
            $request->validated('note'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success('Offline payment confirmed and applied to the invoice.', new LicencePaymentResource($fresh));
    }

    public function rejectOffline(
        RejectOfflinePaymentRequest $request,
        LicencePayment $payment,
        RejectOfflineLicencePaymentAction $action
    ): JsonResponse {
        $fresh = $action->execute(
            $payment,
            $request->user(),
            (string) $request->validated('reason'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success('Offline payment rejected.', new LicencePaymentResource($fresh));
    }

    public function downloadOfflineProof(LicencePayment $payment): mixed
    {
        if ($payment->gateway_name !== 'offline') {
            abort(404);
        }

        $raw = (array) ($payment->raw_response_json ?? []);
        $offline = (array) ($raw['offline'] ?? []);
        $path = (string) ($offline['proof_disk_path'] ?? '');
        $disk = (string) ($offline['proof_disk'] ?? 'local');

        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $name = (string) ($offline['proof_original_name'] ?? 'receipt');

        return Storage::disk($disk)->download($path, $name);
    }

    private function applyStatusFilter(Builder $query, Request $request): void
    {
        if (! $request->filled('payment_status') && ! $request->filled('status')) {
            return;
        }

        match ($request->string('payment_status', $request->string('status')->value())->value()) {
            'paid' => $query->where('payment_status', 'paid'),
            'partially_paid' => $query->where('amount_allocated', '>', 0)->where('balance_after', '>', 0),
            'outstanding' => $query->where('balance_after', '>', 0),
            'pending' => $query->whereIn('payment_status', ['pending', 'processing', 'pending_offline']),
            'pending_offline' => $query->where('payment_status', 'pending_offline'),
            'failed' => $query->where('payment_status', 'failed'),
            'rejected' => $query->where('payment_status', 'cancelled'),
            default => $query->where('payment_status', $request->string('payment_status', $request->string('status')->value())->value()),
        };
    }
}
