<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Actions\Payments\HandlePaymentWebhookAction;
use App\Actions\Payments\VerifyFlutterwaveWebhookSignatureAction;
use App\Actions\Payments\VerifyPaystackWebhookSignatureAction;
use App\Exceptions\InvalidWebhookSignatureException;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentWebhookController extends BaseApiController
{
    public function handle(
        Request $request,
        HandlePaymentWebhookAction $action,
        VerifyPaystackWebhookSignatureAction $verifyPaystackSignature,
        VerifyFlutterwaveWebhookSignatureAction $verifyFlutterwaveSignature
    ): JsonResponse {
        try {
            if ($request->headers->has('x-paystack-signature')) {
                $verifyPaystackSignature->execute($request->getContent(), $request->header('x-paystack-signature'));
            } elseif ($request->headers->has('verif-hash')) {
                $verifyFlutterwaveSignature->execute($request->header('verif-hash'));
            }

            $result = $action->execute($request->all(), $request->ip(), $request->userAgent());

            return $this->success('Webhook processed successfully.', [
                'event' => $result['event'] ?? null,
                'handled' => $result['handled'] ?? true,
                'idempotent' => $result['idempotent'] ?? false,
                'payment_reference' => $result['payment']->payment_reference ?? null,
            ]);
        } catch (InvalidWebhookSignatureException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (ValidationException $e) {
            return $this->error('Webhook payload validation failed.', 422, $e->errors());
        } catch (ModelNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (\Throwable $e) {
            report($e);

            return $this->error('Webhook processing failed.', 500);
        }
    }
}
