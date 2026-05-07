<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\LicencePaymentSummaryStatus;
use App\Enums\LicenceStatus;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\Licence;
use App\Models\User;
use App\Support\References\ReferenceCodeGenerator;
use Illuminate\Support\Facades\DB;

class IssueInstitutionYearlyLicenceAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected ReferenceCodeGenerator $referenceCodeGenerator,
    ) {
    }

    public function execute(
        InstitutionAnnualDeclaration $declaration,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Licence {
        return DB::transaction(function () use ($declaration, $actor, $ipAddress, $userAgent) {
            $institution = $declaration->institution()->lockForUpdate()->firstOrFail();
            $existing = Licence::query()
                ->where('institution_id', $institution->id)
                ->where('licence_year', $declaration->licensing_year)
                ->lockForUpdate()
                ->first();

            $before = $existing?->toArray();
            $paymentStatus = ((float) $declaration->paid_amount) <= 0
                ? LicencePaymentSummaryStatus::Pending->value
                : (((float) $declaration->outstanding_amount) > 0 ? LicencePaymentSummaryStatus::PartiallyPaid->value : LicencePaymentSummaryStatus::Paid->value);

            $licenceStatus = ((float) $declaration->outstanding_amount) > 0 ? LicenceStatus::PendingPayment->value : LicenceStatus::Active->value;

            $licence = Licence::updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'licence_year' => $declaration->licensing_year,
                ],
                [
                    'institution_annual_declaration_id' => $declaration->id,
                    'licence_number' => $existing?->licence_number ?: $this->referenceCodeGenerator->generateLicenceNumber((string) $institution->licence_id, (int) $declaration->licensing_year),
                    'licence_id_snapshot' => $institution->licence_id,
                    'licence_status' => $licenceStatus,
                    'payment_status' => $paymentStatus,
                    'start_date' => now()->startOfYear()->toDateString(),
                    'end_date' => now()->endOfYear()->toDateString(),
                    'amount_due' => $declaration->expected_amount,
                    'amount_paid' => $declaration->paid_amount,
                    'outstanding_amount' => $declaration->outstanding_amount,
                    'issued_by_user_id' => $actor?->id,
                    'issued_at' => now(),
                ]
            );

            $fresh = $licence->fresh(['declaration', 'payments']);

            $this->logAuditAction->execute(
                $actor,
                $before ? 'institution_yearly_licence_updated' : 'institution_yearly_licence_issued',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });
    }
}
