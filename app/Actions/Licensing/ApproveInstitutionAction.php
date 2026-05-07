<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Events\InstitutionApproved;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveInstitutionAction
{
    public function __construct(protected LogAuditAction $logAuditAction) {}

    public function execute(Institution $institution, User $actor, ?string $ipAddress = null, ?string $userAgent = null): Institution
    {
        return DB::transaction(function () use ($institution, $actor, $ipAddress, $userAgent) {
            $institution->loadMissing('legacyDocuments');

            $requiredKycDocuments = collect([
                'cac_certificate' => 'CAC Certificate',
                'proof_of_address' => 'Proof of Address',
            ]);
            $submittedKycTypes = $institution->legacyDocuments
                ->pluck('document_type')
                ->filter()
                ->unique()
                ->values();
            $missingKyc = $requiredKycDocuments
                ->reject(fn (string $label, string $type) => $submittedKycTypes->contains($type));

            if ($missingKyc->isNotEmpty()) {
                $missingLabels = $missingKyc->values()->implode(' and ');

                throw ValidationException::withMessages([
                    'kyc_documents' => ["Institution cannot be approved until the required KYC document(s) have been uploaded: {$missingLabels}."],
                ]);
            }

            $before = $institution->toArray();

            $institution->update([
                'account_status' => 'active',
                'onboarding_status' => 'approved',
                'approved_by_user_id' => $actor->id,
                'approved_at' => now(),
                'licence_id' => $institution->licence_id ?: app(GenerateInstitutionLicenceIdAction::class)->execute($institution),
                'licence_id_generated_at' => $institution->licence_id_generated_at ?: now(),
            ]);

            $fresh = $institution->fresh(['state', 'city']);
            $this->logAuditAction->execute($actor, 'institution_approved', $fresh, $before, $fresh->toArray(), $ipAddress, $userAgent);
            event(new InstitutionApproved($fresh, $actor));

            return $fresh;
        });
    }
}
