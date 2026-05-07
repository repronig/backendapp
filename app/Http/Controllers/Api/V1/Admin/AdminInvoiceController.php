<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Models\Invoice;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInvoiceController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with(['institution', 'declaration', 'licence'])
            ->when($request->filled('status'), function ($q) use ($request) {
                match ($request->string('status')->value()) {
                    'paid' => $q->where('invoice_status', 'paid'),
                    'partially_paid' => $q->where('invoice_status', 'partially_paid'),
                    'outstanding' => $q->where('outstanding_amount', '>', 0)->where(function ($sub) {
                        $sub->whereNull('amount_paid')->orWhere('amount_paid', '<=', 0);
                    }),
                    'pending' => $q->where('invoice_status', 'issued'),
                    'failed', 'rejected' => $q->where('invoice_status', 'cancelled'),
                    default => $q->where('invoice_status', $request->string('status')->value()),
                };
            })
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', $request->integer('institution_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereAnyColumnIlike($sub, ['invoice_number', 'invoice_type'], $search);
                    $sub->orWhereHas('institution', function ($institutionQuery) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($institutionQuery, ['name', 'licence_id'], $search);
                    });
                });
            });

        $this->applyDateRange($invoices, $request, 'issue_date');

        $invoices->latest('issue_date');

        return $this->paginated('Invoices retrieved successfully.', $invoices->paginate($this->perPage($request, 20)), InvoiceResource::class);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return $this->success('Invoice retrieved successfully.', new InvoiceResource($invoice->load(['institution', 'declaration', 'licence', 'payments.reconciledBy', 'adjustments.creator'])));
    }
}
