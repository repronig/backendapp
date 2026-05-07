<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Actions\Licensing\InitiateLicencePaymentAction;
use App\Actions\Licensing\SubmitOfflineInvoicePaymentAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\InitiateInvoicePaymentRequest;
use App\Http\Requests\Api\V1\StoreOfflineInvoicePaymentRequest;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Http\Resources\Api\V1\LicencePaymentResource;
use App\Http\Resources\Api\V1\PaymentInitiationResource;
use App\Models\Invoice;
use App\Support\PostgresSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionInvoiceController extends BaseApiController
{
    protected function resolveInstitutionInvoice(
        Request $request,
        Invoice $invoice,
        ResolveInstitutionForUserAction $resolver
    ): Invoice {
        $institution = $resolver->execute($request->user());
        abort_unless($invoice->institution_id === $institution->id, 404);

        return $invoice;
    }

    public function index(Request $request, ResolveInstitutionForUserAction $resolver): JsonResponse
    {
        $institution = $resolver->execute($request->user());

        $invoices = $institution->invoices()
            ->with(['institution', 'declaration', 'licence'])
            ->when($request->filled('status'), fn ($q) => $q->where('invoice_status', $request->string('status')->value()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereColumnIlike($sub, 'invoice_number', $search);
                    $sub->orWhereHas('licence', function ($licenceQuery) use ($search) {
                        PostgresSearch::whereColumnIlike($licenceQuery, 'licence_number', $search);
                    });
                });
            })
            ->latest('issue_date')
            ->paginate($this->perPage($request));

        return $this->paginated('Invoices retrieved successfully.', $invoices, InvoiceResource::class);
    }

    public function show(Request $request, Invoice $invoice, ResolveInstitutionForUserAction $resolver): JsonResponse
    {
        $invoice = $this->resolveInstitutionInvoice($request, $invoice, $resolver);

        return $this->success('Invoice retrieved successfully.', new InvoiceResource($invoice->load(['institution', 'declaration', 'licence', 'payments.reconciledBy', 'adjustments.creator'])));
    }

    public function initiatePayment(InitiateInvoicePaymentRequest $request, Invoice $invoice, ResolveInstitutionForUserAction $resolver, InitiateLicencePaymentAction $action): JsonResponse
    {
        $invoice = $this->resolveInstitutionInvoice($request, $invoice, $resolver);

        $payment = $action->execute($invoice->licence, $request->user(), (float) $request->validated('amount'), $invoice, (string) $request->validated('gateway_name', 'paystack'), $request->validated('callback_url'), $request->ip(), $request->userAgent());

        return $this->success('Invoice payment initiated successfully.', new PaymentInitiationResource($payment));
    }

    public function submitOfflinePayment(
        StoreOfflineInvoicePaymentRequest $request,
        Invoice $invoice,
        ResolveInstitutionForUserAction $resolver,
        SubmitOfflineInvoicePaymentAction $action
    ): JsonResponse {
        $invoice = $this->resolveInstitutionInvoice($request, $invoice, $resolver);

        $invoice->load('licence');
        abort_unless($invoice->licence, 404);
        $this->authorize('initiatePayment', $invoice->licence);

        $payment = $action->execute(
            $invoice,
            $request->user(),
            (float) $request->validated('amount'),
            (bool) $request->boolean('paid_in_full'),
            $request->validated('institution_note'),
            $request->file('receipt'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created('Offline payment submitted for admin review.', new LicencePaymentResource($payment));
    }
}
