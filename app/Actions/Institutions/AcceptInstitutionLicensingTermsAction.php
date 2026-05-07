<?php

namespace App\Actions\Institutions;

use App\Models\Institution;
use App\Support\Payments\PaymentGatewaySettings;
use Illuminate\Validation\ValidationException;

class AcceptInstitutionLicensingTermsAction
{
    public function __construct(
        protected PaymentGatewaySettings $paymentGatewaySettings,
    ) {}

    public function execute(
        Institution $institution,
        string $termsVersion,
        string $acknowledgedOn,
    ): Institution {
        $published = $this->paymentGatewaySettings->configuredInstitutionLicensingTerms();
        if ($published === null) {
            throw ValidationException::withMessages([
                'terms' => ['Institution licensing terms are not published. Acceptance is not required.'],
            ]);
        }

        if ($published['version'] !== $termsVersion) {
            throw ValidationException::withMessages([
                'terms_version' => ['The published terms version has changed. Please refresh this page and review the latest terms.'],
            ]);
        }

        if ($institution->licensing_terms_accepted_at !== null) {
            return $institution->fresh(['profile', 'legacyDocuments']);
        }

        $institution->update([
            'licensing_terms_accepted_at' => now(),
            'licensing_terms_acknowledged_on' => $acknowledgedOn,
            'licensing_terms_version_accepted' => $published['version'],
        ]);

        return $institution->fresh(['profile', 'legacyDocuments']);
    }
}
