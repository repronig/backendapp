<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicencePaymentResource extends JsonResource
{
    protected function settlementSummary(): array
    {
        $amount = (float) ($this->amount ?? 0);
        $allocated = (float) ($this->amount_allocated ?? 0);
        $balanceAfter = max((float) ($this->balance_after ?? 0), 0);
        $dueDate = $this->invoice?->due_date ?? $this->declaration?->invoice_due_date;
        $isOverdue = $balanceAfter > 0 && $dueDate && now()->startOfDay()->gt($dueDate->copy()->startOfDay());

        $state = $balanceAfter <= 0 && $allocated > 0
            ? 'fully_paid'
            : ($allocated > 0 && $balanceAfter > 0
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
            'amount' => $amount,
            'amount_allocated' => $allocated,
            'balance_after' => $balanceAfter,
        ];
    }

    protected function auditSummary(): array
    {
        $reconciliations = $this->resource->relationLoaded('reconciliations') ? $this->resource->reconciliations : collect();

        $recentActions = collect()
            ->push([
                'id' => 'payment-'.$this->id,
                'type' => 'payment',
                'label' => 'Payment '.($this->payment_status ? ucwords(str_replace('_', ' ', (string) $this->payment_status)) : 'Recorded'),
                'description' => $this->payment_reference ?: $this->gateway_reference,
                'amount' => (float) ($this->amount_allocated ?? $this->amount ?? 0),
                'created_at' => optional($this->paid_at ?? $this->created_at)?->toISOString(),
                'actor' => [
                    'id' => $this->reconciledBy?->id,
                    'name' => $this->reconciledBy?->name,
                ],
            ])
            ->merge($reconciliations->map(fn ($reconciliation) => [
                'id' => 'reconciliation-'.$reconciliation->id,
                'type' => 'reconciliation',
                'label' => 'Reconciliation '.($reconciliation->status ? ucwords(str_replace('_', ' ', (string) $reconciliation->status)) : 'Recorded'),
                'description' => $reconciliation->note ?: $reconciliation->reason_code,
                'amount' => (float) ($this->amount_allocated ?? $this->amount ?? 0),
                'created_at' => optional($reconciliation->processed_at ?? $reconciliation->created_at)?->toISOString(),
                'actor' => [
                    'id' => $reconciliation->processor?->id,
                    'name' => $reconciliation->processor?->name,
                ],
            ]))
            ->filter(fn ($action) => filled($action['created_at'] ?? null))
            ->sortByDesc('created_at')
            ->values();

        return [
            'reconciliation_count' => $reconciliations->count(),
            'recent_action_count' => $recentActions->count(),
            'last_action_at' => $recentActions->first()['created_at'] ?? null,
            'recent_actions' => $recentActions->take(5)->all(),
        ];
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'licence_id' => $this->licence_id,
            'institution_annual_declaration_id' => $this->institution_annual_declaration_id,
            'invoice_id' => $this->invoice_id,
            'payment_reference' => $this->payment_reference,
            'gateway_reference' => $this->gateway_reference,
            'provider_event_id' => $this->provider_event_id,
            'gateway_name' => $this->gateway_name,
            'offline' => $this->when($this->gateway_name === 'offline', fn () => [
                'paid_in_full' => (bool) data_get($this->raw_response_json, 'offline.paid_in_full', false),
                'institution_note' => (string) data_get($this->raw_response_json, 'offline.institution_note', ''),
                'has_proof' => filled(data_get($this->raw_response_json, 'offline.proof_disk_path')),
                'submitted_at' => data_get($this->raw_response_json, 'offline.submitted_at'),
                'rejection_reason' => (string) data_get($this->raw_response_json, 'offline.rejection_reason', ''),
            ]),
            'amount' => $this->amount,
            'amount_allocated' => $this->amount_allocated,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at,
            'institution' => $this->whenLoaded('institution', fn () => [
                'id' => $this->institution?->id,
                'name' => $this->institution?->name,
                'email' => $this->institution?->email,
                'licence_id' => $this->institution?->licence_id,
            ]),
            'licence' => $this->whenLoaded('licence', fn () => [
                'id' => $this->licence?->id,
                'licence_number' => $this->licence?->licence_number,
                'licence_year' => $this->licence?->licence_year,
                'licence_status' => $this->licence?->licence_status,
                'payment_status' => $this->licence?->payment_status,
            ]),
            'declaration' => $this->whenLoaded('declaration', fn () => [
                'id' => $this->declaration?->id,
                'licensing_year' => $this->declaration?->licensing_year,
                'declaration_status' => $this->declaration?->declaration_status,
                'expected_amount' => $this->declaration?->expected_amount,
                'outstanding_amount' => $this->declaration?->outstanding_amount,
                'invoice_due_date' => optional($this->declaration?->invoice_due_date)->toDateString(),
            ]),
            'invoice' => $this->whenLoaded('invoice', fn () => [
                'id' => $this->invoice?->id,
                'invoice_number' => $this->invoice?->invoice_number,
                'status' => $this->invoice?->invoice_status,
                'total_amount' => $this->invoice?->total_amount,
                'outstanding_amount' => $this->invoice?->outstanding_amount,
                'due_date' => optional($this->invoice?->due_date)->toDateString(),
            ]),
            'settlement_summary' => $this->settlementSummary(),
            'audit_summary' => $this->auditSummary(),
            'related_entities' => [
                'institution' => $this->whenLoaded('institution', fn () => [
                    'id' => $this->institution?->id,
                    'name' => $this->institution?->name,
                    'licence_id' => $this->institution?->licence_id,
                ]),
                'licence' => $this->whenLoaded('licence', fn () => [
                    'id' => $this->licence?->id,
                    'licence_number' => $this->licence?->licence_number,
                    'licence_status' => $this->licence?->licence_status,
                ]),
                'declaration' => $this->whenLoaded('declaration', fn () => [
                    'id' => $this->declaration?->id,
                    'licensing_year' => $this->declaration?->licensing_year,
                    'declaration_status' => $this->declaration?->declaration_status,
                ]),
                'invoice' => $this->whenLoaded('invoice', fn () => [
                    'id' => $this->invoice?->id,
                    'invoice_number' => $this->invoice?->invoice_number,
                    'status' => $this->invoice?->invoice_status,
                    'due_date' => optional($this->invoice?->due_date)->toDateString(),
                ]),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
