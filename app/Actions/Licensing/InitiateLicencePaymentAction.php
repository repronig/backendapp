<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\LicencePaymentStatus;
use App\Models\Invoice;
use App\Models\Licence;
use App\Models\LicencePayment;
use App\Models\User;
use App\Notifications\System\PaymentInitiatedSystemNotification;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use App\Support\Payments\PaymentGatewaySettings;
use App\Support\References\ReferenceCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InitiateLicencePaymentAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected ReferenceCodeGenerator $referenceCodeGenerator,
        protected SystemNotificationService $systemNotifications,
        protected MailService $mailService,
        protected PaymentGatewaySettings $paymentGatewaySettings,
    ) {}

    public function execute(
        Licence $licence,
        ?User $actor,
        float $amount,
        ?Invoice $invoice = null,
        string $gatewayName = 'paystack',
        ?string $callbackUrl = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): LicencePayment {
        $invoice ??= $licence->invoice;

        if ($gatewayName === 'offline') {
            throw ValidationException::withMessages([
                'gateway_name' => ['Offline payments must be submitted using the offline payment endpoint, not the online checkout flow.'],
            ]);
        }

        $this->paymentGatewaySettings->assertOnlineGatewayEnabled($gatewayName);

        if (! $invoice) {
            throw ValidationException::withMessages(['invoice' => ['No active invoice is available for this licence.']]);
        }

        if ($amount <= 0 || $amount > (float) $invoice->outstanding_amount) {
            throw ValidationException::withMessages(['amount' => ['Payment amount must be greater than zero and not exceed the outstanding invoice balance.']]);
        }

        return DB::transaction(function () use ($licence, $actor, $amount, $invoice, $gatewayName, $callbackUrl, $ipAddress, $userAgent) {
            $paymentReference = $this->referenceCodeGenerator->generatePaymentReference();

            $payment = LicencePayment::create([
                'licence_id' => $licence->id,
                'institution_id' => $licence->institution_id,
                'institution_annual_declaration_id' => $licence->institution_annual_declaration_id,
                'invoice_id' => $invoice->id,
                'payment_reference' => $paymentReference,
                'gateway_name' => $gatewayName,
                'amount' => round($amount, 2),
                'amount_allocated' => 0,
                'balance_before' => $invoice->outstanding_amount,
                'balance_after' => $invoice->outstanding_amount,
                'currency' => $invoice->currency,
                'payment_status' => LicencePaymentStatus::Pending->value,
                'raw_response_json' => array_filter([
                    'callback_url' => $callbackUrl,
                    'authorization_url' => $this->buildGatewayAuthorizationUrl($gatewayName, $paymentReference, $amount, $callbackUrl),
                ]),
            ]);

            if ($actor) {
                $this->logAuditAction->execute($actor, 'licence_payment_initiated', $payment, null, $payment->toArray(), $ipAddress, $userAgent);
            }

            $licence->loadMissing('institution.institutionUsers.user');
            foreach ($licence->institution?->institutionUsers ?? [] as $institutionUser) {
                if (! $institutionUser->is_active || ! $institutionUser->user) {
                    continue;
                }

                $this->systemNotifications->send(
                    $institutionUser->user,
                    new PaymentInitiatedSystemNotification(
                        number_format((float) $payment->amount, 2).' '.$payment->currency,
                        $payment->payment_reference,
                        $payment->id,
                        $licence->licence_number ?? $licence->external_id ?? null,
                    ),
                    'payment_initiated',
                    'Payment initiated'
                );
            }

            if ($licence->institution) {
                $this->mailService->sendPaymentInitiated($licence->institution, $payment->load('licence'));
            }

            return $payment;
        });
    }

    protected function buildGatewayAuthorizationUrl(string $gatewayName, string $reference, float $amount, ?string $callbackUrl): ?string
    {
        $query = http_build_query(array_filter([
            'reference' => $reference,
            'amount' => round($amount, 2),
            'callback_url' => $callbackUrl,
        ]));

        return match ($gatewayName) {
            'paystack' => $query ? "https://checkout.paystack.com/{$reference}?{$query}" : "https://checkout.paystack.com/{$reference}",
            'flutterwave' => $query ? "https://checkout.flutterwave.com/v3/hosted/pay?{$query}" : 'https://checkout.flutterwave.com/v3/hosted/pay',
            default => null,
        };
    }
}
