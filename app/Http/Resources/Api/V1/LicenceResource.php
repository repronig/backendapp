<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenceResource extends JsonResource
{
    protected function settlementSummary(): array
    {
        $due = (float) ($this->amount_due ?? 0);
        $paid = (float) ($this->amount_paid ?? 0);
        $outstanding = max((float) ($this->outstanding_amount ?? max($due - $paid, 0)), 0);
        $dueDate = $this->invoice?->due_date ?? $this->end_date;
        $isOverdue = $outstanding > 0 && $dueDate && now()->startOfDay()->gt($dueDate->copy()->startOfDay());

        $state = $outstanding <= 0 && $due > 0
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
            'amount_due' => $due,
            'amount_paid' => $paid,
            'outstanding_amount' => $outstanding,
        ];
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'institution_annual_declaration_id' => $this->institution_annual_declaration_id,
            'licence_number' => $this->licence_number,
            'licence_id_snapshot' => $this->licence_id_snapshot,
            'licence_year' => $this->licence_year,
            'agreement_version' => $this->agreement_version,
            'licence_status' => $this->licence_status,
            'payment_status' => $this->payment_status,
            'start_date' => optional($this->start_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'negotiated_rate' => $this->negotiated_rate,
            'amount_due' => $this->amount_due,
            'amount_paid' => $this->amount_paid,
            'outstanding_amount' => $this->outstanding_amount,
            'issued_at' => $this->issued_at,
            'institution' => $this->whenLoaded('institution', fn () => [
                'id' => $this->institution?->id,
                'name' => $this->institution?->name,
                'email' => $this->institution?->email,
                'licence_id' => $this->institution?->licence_id,
                'institution_type' => $this->institution?->institution_type,
            ]),
            'declaration' => $this->whenLoaded('declaration', fn () => [
                'id' => $this->declaration?->id,
                'licensing_year' => $this->declaration?->licensing_year,
                'declaration_status' => $this->declaration?->declaration_status,
                'basis_type' => $this->declaration?->basis_type,
                'expected_amount' => $this->declaration?->expected_amount,
                'paid_amount' => $this->declaration?->paid_amount,
                'outstanding_amount' => $this->declaration?->outstanding_amount,
                'invoice_due_date' => optional($this->declaration?->invoice_due_date)->toDateString(),
            ]),
            'invoice' => $this->whenLoaded('invoice', fn () => [
                'id' => $this->invoice?->id,
                'invoice_number' => $this->invoice?->invoice_number,
                'status' => $this->invoice?->invoice_status,
                'total_amount' => $this->invoice?->total_amount,
                'amount_paid' => $this->invoice?->amount_paid,
                'outstanding_amount' => $this->invoice?->outstanding_amount,
                'due_date' => optional($this->invoice?->due_date)->toDateString(),
            ]),
            'payments' => LicencePaymentResource::collection($this->whenLoaded('payments')),
            'financial_summary' => [
                'amount_due' => $this->amount_due,
                'amount_paid' => $this->amount_paid,
                'outstanding_amount' => $this->outstanding_amount,
                'payment_count' => $this->whenCounted('payments', fn () => $this->payments_count),
            ],
            'settlement_summary' => $this->settlementSummary(),
            'related_entities' => [
                'institution' => $this->whenLoaded('institution', fn () => [
                    'id' => $this->institution?->id,
                    'name' => $this->institution?->name,
                    'licence_id' => $this->institution?->licence_id,
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
