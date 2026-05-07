<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    protected function settlementSummary(): array
    {
        $total = (float) ($this->total_amount ?? 0);
        $paid = (float) ($this->amount_paid ?? 0);
        $outstanding = max((float) ($this->outstanding_amount ?? max($total - $paid, 0)), 0);
        $dueDate = $this->due_date;
        $isOverdue = $outstanding > 0 && $dueDate && now()->startOfDay()->gt($dueDate->copy()->startOfDay());

        $state = $outstanding <= 0 && $total > 0
            ? 'fully_paid'
            : ($paid > 0 && $outstanding > 0
                ? 'partially_paid'
                : ($isOverdue ? 'overdue' : 'outstanding'));

        return [
            'state' => $state,
            'label' => match ($state) {
                'fully_paid' => 'Fully paid',
                'partially_paid' => 'Partially paid',
                'overdue' => 'Overdue',
                default => 'Outstanding',
            },
            'is_fully_paid' => $state === 'fully_paid',
            'is_partially_paid' => $state === 'partially_paid',
            'is_outstanding' => in_array($state, ['outstanding', 'overdue'], true),
            'is_overdue' => $state === 'overdue',
            'due_date' => optional($dueDate)?->toDateString(),
            'total_amount' => $total,
            'amount_paid' => $paid,
            'outstanding_amount' => $outstanding,
        ];
    }

    protected function auditSummary(): array
    {
        $adjustments = $this->resource->relationLoaded('adjustments') ? $this->resource->adjustments : collect();
        $payments = $this->resource->relationLoaded('payments') ? $this->resource->payments : collect();

        $adjustmentActions = collect($adjustments)->map(fn ($adjustment) => [
            'id' => 'adjustment-'.$adjustment->id,
            'type' => 'adjustment',
            'label' => ucwords(str_replace('_', ' ', (string) $adjustment->adjustment_type)),
            'description' => $adjustment->reason ?: $adjustment->reason_code,
            'amount' => (float) ($adjustment->amount ?? 0),
            'created_at' => optional($adjustment->applied_at ?? $adjustment->created_at)?->toISOString(),
            'actor' => [
                'id' => $adjustment->creator?->id,
                'name' => $adjustment->creator?->name,
            ],
        ]);

        $paymentActions = collect($payments)->map(fn ($payment) => [
            'id' => 'payment-'.$payment->id,
            'type' => 'payment',
            'label' => 'Payment '.($payment->payment_status ? ucwords(str_replace('_', ' ', (string) $payment->payment_status)) : 'Recorded'),
            'description' => $payment->payment_reference ?: $payment->gateway_reference,
            'amount' => (float) ($payment->amount_allocated ?? $payment->amount ?? 0),
            'created_at' => optional($payment->paid_at ?? $payment->created_at)?->toISOString(),
            'actor' => [
                'id' => $payment->reconciledBy?->id,
                'name' => $payment->reconciledBy?->name,
            ],
        ]);

        $recentActions = $adjustmentActions
            ->concat($paymentActions)
            ->sortByDesc('created_at')
            ->values();
        return [
            'adjustment_count' => $adjustments->count(),
            'payment_count' => $payments->count(),
            'recent_action_count' => $recentActions->count(),
            'last_action_at' => $recentActions->first()['created_at'] ?? null,
            'recent_actions' => $recentActions->take(5)->all(),
        ];
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_type' => $this->invoice_type,
            'billing_year' => $this->billing_year,
            'issue_date' => optional($this->issue_date)->toDateString(),
            'due_date' => optional($this->due_date)->toDateString(),
            'status' => $this->invoice_status,
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'outstanding_amount' => $this->outstanding_amount,
            'institution' => $this->whenLoaded('institution', fn () => [
                'id' => $this->institution?->id,
                'name' => $this->institution?->name,
                'email' => $this->institution?->email,
                'licence_id' => $this->institution?->licence_id,
                'logo_url' => $this->institution?->logo_url,
                'logo_thumb_url' => $this->institution?->logo_thumb_url,
                'logo_medium_url' => $this->institution?->logo_medium_url,
            ]),
            'declaration' => $this->whenLoaded('declaration', fn () => [
                'id' => $this->declaration?->id,
                'licensing_year' => $this->declaration?->licensing_year,
                'declaration_status' => $this->declaration?->declaration_status,
            ]),
            'licence' => $this->whenLoaded('licence', fn () => [
                'id' => $this->licence?->id,
                'licence_number' => $this->licence?->licence_number,
                'licence_status' => $this->licence?->licence_status,
                'payment_status' => $this->licence?->payment_status,
            ]),
            'payments' => $this->whenLoaded('payments', fn () => LicencePaymentResource::collection($this->payments)),
            'adjustments' => $this->whenLoaded('adjustments', fn () => $this->adjustments->map(fn ($adjustment) => [
                'id' => $adjustment->id,
                'adjustment_type' => $adjustment->adjustment_type,
                'amount' => (float) ($adjustment->amount ?? 0),
                'reason_code' => $adjustment->reason_code,
                'reason' => $adjustment->reason,
                'applied_at' => optional($adjustment->applied_at)?->toISOString(),
                'created_by' => [
                    'id' => $adjustment->creator?->id,
                    'name' => $adjustment->creator?->name,
                ],
            ])->values()),
            'settlement_summary' => $this->settlementSummary(),
            'audit_summary' => $this->auditSummary(),
            'related_entities' => [
                'institution' => $this->whenLoaded('institution', fn () => [
                    'id' => $this->institution?->id,
                    'name' => $this->institution?->name,
                    'licence_id' => $this->institution?->licence_id,
                    'logo_url' => $this->institution?->logo_url,
                    'logo_thumb_url' => $this->institution?->logo_thumb_url,
                    'logo_medium_url' => $this->institution?->logo_medium_url,
                ]),
                'declaration' => $this->whenLoaded('declaration', fn () => [
                    'id' => $this->declaration?->id,
                    'licensing_year' => $this->declaration?->licensing_year,
                    'declaration_status' => $this->declaration?->declaration_status,
                ]),
                'licence' => $this->whenLoaded('licence', fn () => [
                    'id' => $this->licence?->id,
                    'licence_number' => $this->licence?->licence_number,
                    'licence_status' => $this->licence?->licence_status,
                    'payment_status' => $this->licence?->payment_status,
                ]),
            ],
        ];
    }
}
