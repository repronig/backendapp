<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\DeclarationStatus;
use App\Events\InstitutionAnnualDeclarationApproved;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveInstitutionAnnualDeclarationAction
{
    public function __construct(
        protected CalculateInstitutionDeclarationExpectedAmountAction $calculateInstitutionDeclarationExpectedAmountAction,
        protected IssueInstitutionYearlyLicenceAction $issueInstitutionYearlyLicenceAction,
        protected GenerateInstitutionInvoiceAction $generateInstitutionInvoiceAction,
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(InstitutionAnnualDeclaration $declaration, User $actor, ?string $ipAddress = null, ?string $userAgent = null): InstitutionAnnualDeclaration
    {
        if (! in_array($declaration->declaration_status, [DeclarationStatus::Submitted->value, DeclarationStatus::UnderReview->value], true)) {
            throw ValidationException::withMessages([
                'declaration_status' => ['Only submitted or under-review declarations can be approved.'],
            ]);
        }

        return DB::transaction(function () use ($declaration, $actor, $ipAddress, $userAgent) {
            $declaration = $declaration->fresh(['institution', 'faculties']);
            $before = $declaration->toArray();

            $calculated = $this->calculateInstitutionDeclarationExpectedAmountAction->execute(
                $declaration->institution,
                [
                    'declared_students_count' => $declaration->declared_students_count,
                    'declared_members_count' => $declaration->declared_members_count,
                    'declared_branches_count' => $declaration->declared_branches_count,
                    'faculties' => $declaration->faculties
                        ->map(fn ($faculty): array => [
                            'faculty_name' => $faculty->faculty_name,
                            'student_count' => (int) $faculty->student_count,
                        ])
                        ->all(),
                ],
                (int) $declaration->licensing_year
            );

            $paidAmount = (float) $declaration->paid_amount;
            $expectedAmount = (float) $calculated['expected_amount'];

            $declaration->update([
                'licence_id_snapshot' => $declaration->institution->licence_id,
                'basis_type' => $calculated['basis_type'],
                'declared_units' => $calculated['declared_units'],
                'pricing_unit_cost' => $calculated['pricing_unit_cost'],
                'pricing_flat_amount' => $calculated['pricing_flat_amount'],
                'expected_amount' => $expectedAmount,
                'outstanding_amount' => max($expectedAmount - $paidAmount, 0),
                'declaration_status' => DeclarationStatus::Approved->value,
                'approved_at' => now(),
                'approved_by_user_id' => $actor->id,
                'invoice_due_date' => now()->addDays((int) config('licensing.invoice_due_days', 14))->toDateString(),
            ]);

            $freshForFinance = $declaration->fresh(['institution', 'faculties']);
            $this->issueInstitutionYearlyLicenceAction->execute($freshForFinance, $actor, $ipAddress, $userAgent);
            $this->generateInstitutionInvoiceAction->execute($freshForFinance->fresh(['institution', 'licence']), $actor);

            $fresh = $declaration->fresh(['faculties', 'licence', 'institution', 'invoice']);
            $this->logAuditAction->execute($actor, 'institution_annual_declaration_approved', $fresh, $before, $fresh->toArray(), $ipAddress, $userAgent);
            event(new InstitutionAnnualDeclarationApproved($fresh, $actor));

            return $fresh;
        });
    }
}
