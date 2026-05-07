<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\InvoiceStatus;
use App\Events\InstitutionInvoiceGenerated;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\Invoice;
use App\Models\User;
use App\Support\References\ReferenceCodeGenerator;
use Illuminate\Support\Facades\DB;

class GenerateInstitutionInvoiceAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected ReferenceCodeGenerator $referenceCodeGenerator,
    ) {}

    public function execute(InstitutionAnnualDeclaration $declaration, ?User $actor = null): Invoice
    {
        return DB::transaction(function () use ($declaration, $actor) {
            $declaration = $declaration->fresh(['institution', 'licence', 'invoice']);
            $existing = $declaration->invoice;
            if ($existing) {
                $existing->syncStatus();
                return $existing->fresh(['institution', 'declaration', 'licence']);
            }

            $licence = $declaration->licence ?: $declaration->institution?->licences()
                ->where('licence_year', $declaration->licensing_year)
                ->latest('id')
                ->first();

            $totalAmount = round((float) $declaration->expected_amount, 2);
            $amountPaid = round((float) $declaration->paid_amount, 2);
            $outstandingAmount = max(round($totalAmount - $amountPaid, 2), 0);

            if ($totalAmount <= 0) {
                $totalAmount = round((float) $declaration->outstanding_amount, 2);
                $amountPaid = 0.0;
                $outstandingAmount = $totalAmount;
            }

            $invoice = Invoice::create([
                'invoice_number' => $this->referenceCodeGenerator->generateInvoiceNumber((int) $declaration->licensing_year, (int) $declaration->institution_id),
                'institution_id' => $declaration->institution_id,
                'institution_annual_declaration_id' => $declaration->id,
                'licence_id' => $licence?->id,
                'invoice_type' => 'licence_fee',
                'billing_year' => $declaration->licensing_year,
                'issue_date' => now()->toDateString(),
                'due_date' => optional($declaration->invoice_due_date ?? now()->addDays((int) config('licensing.invoice_due_days', 14)))->toDateString(),
                'subtotal_amount' => $totalAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'outstanding_amount' => $outstandingAmount,
                'invoice_status' => InvoiceStatus::Issued->value,
                'currency' => 'NGN',
                'metadata_json' => [
                    'basis_type' => $declaration->basis_type,
                    'declared_units' => $declaration->declared_units,
                    'pricing_unit_cost' => $declaration->pricing_unit_cost,
                    'pricing_flat_amount' => $declaration->pricing_flat_amount,
                ],
                'issued_at' => now(),
            ]);

            $this->logAuditAction->execute(
                $actor,
                'institution_invoice_generated',
                $invoice,
                null,
                $invoice->toArray(),
                null,
                null
            );

            event(new InstitutionInvoiceGenerated($invoice->fresh(['institution', 'declaration', 'licence'])));

            return $invoice;
        });
    }
}
