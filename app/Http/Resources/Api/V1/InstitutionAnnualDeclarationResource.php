<?php

namespace App\Http\Resources\Api\V1;

use App\Support\PublicAssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionAnnualDeclarationResource extends JsonResource
{
    protected function settlementSummary(): array
    {
        $expected = (float) ($this->expected_amount ?? 0);
        $paid = (float) ($this->paid_amount ?? 0);
        $outstanding = max((float) ($this->outstanding_amount ?? max($expected - $paid, 0)), 0);
        $dueDate = $this->invoice?->due_date ?? $this->invoice_due_date;
        $isOverdue = $outstanding > 0 && $dueDate && now()->startOfDay()->gt($dueDate->copy()->startOfDay());

        $state = $outstanding <= 0 && $expected > 0
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
            'expected_amount' => $expected,
            'paid_amount' => $paid,
            'outstanding_amount' => $outstanding,
        ];
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'licence_id_snapshot' => $this->licence_id_snapshot,
            'licensing_year' => $this->licensing_year,
            'basis_type' => $this->basis_type,
            'declared_units' => $this->declared_units,
            'declared_students_count' => $this->declared_students_count,
            'declared_members_count' => $this->declared_members_count,
            'declared_branches_count' => $this->declared_branches_count,
            'declared_faculties_count' => $this->declared_faculties_count,
            'pricing_unit_cost' => $this->pricing_unit_cost,
            'pricing_flat_amount' => $this->pricing_flat_amount,
            'expected_amount' => $this->expected_amount,
            'paid_amount' => $this->paid_amount,
            'outstanding_amount' => $this->outstanding_amount,
            'declaration_status' => $this->declaration_status,
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'invoice_due_date' => optional($this->invoice_due_date)->toDateString(),
            'supporting_document' => $this->supporting_document_path ? [
                'file_name' => $this->supporting_document_name,
                'mime_type' => $this->supporting_document_mime_type,
                'file_size' => $this->supporting_document_size,
                'file_path' => $this->supporting_document_path,
                'file_url' => PublicAssetUrl::fromPath($this->supporting_document_path, null, $request),
                'download_url' => PublicAssetUrl::fromPath($this->supporting_document_path, null, $request),
            ] : null,
            'institution' => $this->whenLoaded('institution', fn () => [
                'id' => $this->institution?->id,
                'name' => $this->institution?->name,
                'email' => $this->institution?->email,
                'licence_id' => $this->institution?->licence_id,
                'institution_type' => $this->institution?->institution_type,
            ]),
            'faculties' => InstitutionDeclarationFacultyResource::collection($this->whenLoaded('faculties')),
            'licence' => $this->whenLoaded('licence', fn () => [
                'id' => $this->licence?->id,
                'licence_number' => $this->licence?->licence_number,
                'licence_year' => $this->licence?->licence_year,
                'licence_status' => $this->licence?->licence_status,
                'payment_status' => $this->licence?->payment_status,
                'amount_due' => $this->licence?->amount_due,
                'amount_paid' => $this->licence?->amount_paid,
                'outstanding_amount' => $this->licence?->outstanding_amount,
            ]),
            'invoice' => $this->whenLoaded('invoice', fn () => [
                'id' => $this->invoice?->id,
                'invoice_number' => $this->invoice?->invoice_number,
                'status' => $this->invoice?->invoice_status,
                'billing_year' => $this->invoice?->billing_year,
                'total_amount' => $this->invoice?->total_amount,
                'amount_paid' => $this->invoice?->amount_paid,
                'outstanding_amount' => $this->invoice?->outstanding_amount,
                'due_date' => optional($this->invoice?->due_date)->toDateString(),
            ]),
            'payments' => LicencePaymentResource::collection($this->whenLoaded('payments')),
            'financial_summary' => [
                'expected_amount' => $this->expected_amount,
                'paid_amount' => $this->paid_amount,
                'outstanding_amount' => $this->outstanding_amount,
                'payment_count' => $this->whenCounted('payments', fn () => $this->payments_count),
                'faculty_count' => $this->whenCounted('faculties', fn () => $this->faculties_count),
            ],
            'settlement_summary' => $this->settlementSummary(),
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
                    'payment_status' => $this->licence?->payment_status,
                ]),
                'invoice' => $this->whenLoaded('invoice', fn () => [
                    'id' => $this->invoice?->id,
                    'invoice_number' => $this->invoice?->invoice_number,
                    'status' => $this->invoice?->invoice_status,
                    'due_date' => optional($this->invoice?->due_date)->toDateString(),
                ]),
            ],
            'metadata' => $this->metadata_json,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
