<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Actions\Licensing\InitiateLicencePaymentAction;
use App\Actions\Licensing\SyncInvoiceFromPaymentAction;
use App\Enums\LicencePaymentStatus;
use App\Events\LicencePaymentReceived;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\InitiateLicencePaymentRequest;
use App\Http\Resources\Api\V1\LicencePaymentResource;
use App\Http\Resources\Api\V1\PaymentInitiationResource;
use App\Models\Licence;
use App\Models\LicencePayment;
use App\Support\Pdf\InstitutionPdfPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class InstitutionPaymentController extends BaseApiController
{
    protected function resolveInstitutionPayment(
        Request $request,
        LicencePayment $payment,
        ResolveInstitutionForUserAction $resolver
    ): LicencePayment {
        $institution = $resolver->execute($request->user());
        abort_unless((int) $payment->institution_id === (int) $institution->id, 404);

        return $payment;
    }

    public function index(Request $request, Licence $licence): JsonResponse
    {
        $this->authorize('viewPayments', $licence);

        return $this->success(
            'Licence payments retrieved successfully.',
            LicencePaymentResource::collection($licence->payments()->latest()->get())
        );
    }

    public function initiate(
        InitiateLicencePaymentRequest $request,
        Licence $licence,
        InitiateLicencePaymentAction $action
    ): JsonResponse {
        $this->authorize('initiatePayment', $licence);

        $result = $action->execute(
            $licence,
            $request->user(),
            (float) $request->validated('amount'),
            null,
            (string) $request->validated('gateway_name', 'paystack'),
            $request->validated('callback_url'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success('Payment initiated successfully.', new PaymentInitiationResource($result));
    }

    public function verify(
        Request $request,
        LicencePayment $payment,
        ResolveInstitutionForUserAction $resolver,
        SyncInvoiceFromPaymentAction $syncInvoiceFromPaymentAction
    ): JsonResponse {
        $payment = $this->resolveInstitutionPayment($request, $payment, $resolver);

        if ($payment->payment_status === LicencePaymentStatus::Paid->value) {
            return $this->success(
                'Payment already verified.',
                new LicencePaymentResource($payment->fresh(['invoice', 'licence', 'declaration', 'institution']))
            );
        }

        if (! in_array($payment->gateway_name, ['paystack', 'flutterwave'], true)) {
            throw ValidationException::withMessages([
                'gateway_name' => ['Only Paystack and Flutterwave payments can be verified from this endpoint.'],
            ]);
        }

        $response = $this->verifyWithGateway($payment);
        $data = (array) $response->json('data', []);
        $status = (string) data_get($data, 'status', '');
        $successStatus = $payment->gateway_name === 'flutterwave' ? 'successful' : 'success';

        if (! $response->ok() || ! $response->json('status') || $status !== $successStatus) {
            $payment->update([
                // Keep manual verification retryable. Gateway confirmation can lag after checkout,
                // so only webhooks should permanently move a pending payment to failed.
                'raw_response_json' => $response->json() ?: ['message' => $response->body()],
            ]);

            return $this->error(
                $response->json('message') ?: 'Payment verification is not confirmed yet. Please try again shortly.',
                422,
                ['payment' => ['Payment could not be verified with '.ucfirst($payment->gateway_name).'.']]
            );
        }

        $amountPaid = round(((float) data_get($data, 'amount', 0)), 2);
        if ($payment->gateway_name === 'paystack') {
            $amountPaid = round($amountPaid / 100, 2);
        }

        if ($amountPaid <= 0) {
            $amountPaid = round((float) $payment->amount, 2);
        }

        $fresh = DB::transaction(function () use ($payment, $amountPaid, $response, $data, $syncInvoiceFromPaymentAction): LicencePayment {
            $locked = LicencePayment::query()
                ->with(['invoice', 'licence', 'declaration', 'institution'])
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($locked->payment_status !== LicencePaymentStatus::Paid->value) {
                $locked->update([
                    'gateway_reference' => (string) (data_get($data, 'id') ?: data_get($data, 'flw_ref') ?: data_get($data, 'reference') ?: $locked->payment_reference),
                    'payment_status' => LicencePaymentStatus::Paid->value,
                    'amount_allocated' => $amountPaid,
                    'paid_at' => now(),
                    'processed_at' => now(),
                    'balance_after' => max(round((float) $locked->balance_before - $amountPaid, 2), 0),
                    'raw_response_json' => $response->json(),
                ]);
            }

            $freshPayment = $locked->fresh(['invoice', 'licence', 'declaration', 'institution']);
            $syncInvoiceFromPaymentAction->execute($freshPayment);

            return $freshPayment->fresh(['invoice', 'licence', 'declaration', 'institution']);
        });

        event(new LicencePaymentReceived($fresh));

        return $this->success('Payment verified successfully.', new LicencePaymentResource($fresh));
    }

    public function receipt(
        Request $request,
        LicencePayment $payment,
        ResolveInstitutionForUserAction $resolver,
        InstitutionPdfPresenter $presenter
    ): Response {
        $payment = $this->resolveInstitutionPayment($request, $payment, $resolver);

        $this->authorize('downloadReceipt', $payment);

        $payment->loadMissing(['institution', 'invoice', 'licence']);

        $data = $presenter->paymentReceiptData($payment);
        $ref = $payment->payment_reference ?: 'payment-'.$payment->id;
        $filename = 'payment-receipt-'.preg_replace('/[^a-zA-Z0-9._-]+/', '-', $ref).'.pdf';

        return Pdf::loadView('pdf.payment-receipt', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    protected function verifyWithGateway(LicencePayment $payment)
    {
        if ($payment->gateway_name === 'flutterwave') {
            $secretKey = config('services.flutterwave.secret_key');
            if (! $secretKey) {
                throw ValidationException::withMessages(['flutterwave' => ['Flutterwave secret key is not configured.']]);
            }

            return Http::withToken($secretKey)
                ->acceptJson()
                ->get('https://api.flutterwave.com/v3/transactions/verify_by_reference', [
                    'tx_ref' => $payment->payment_reference,
                ]);
        }

        $secretKey = config('services.paystack.secret_key');
        if (! $secretKey) {
            throw ValidationException::withMessages(['paystack' => ['Paystack secret key is not configured.']]);
        }

        return Http::withToken($secretKey)
            ->acceptJson()
            ->get('https://api.paystack.co/transaction/verify/'.$payment->payment_reference);
    }
}
