<?php

namespace App\Actions\Licensing;

use App\Models\Institution;
use App\Models\Licence;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApplyForLicenceAction
{
    public function __construct(
        protected IssueInstitutionYearlyLicenceAction $issueInstitutionYearlyLicenceAction
    ) {
    }

    public function execute(
        Institution $institution,
        int $licenceYear,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Licence {
        $declaration = $institution->annualDeclarations()
            ->where('licensing_year', $licenceYear)
            ->where('declaration_status', 'approved')
            ->with('institution')
            ->first();

        if (! $declaration) {
            throw ValidationException::withMessages([
                'licensing_year' => ['An approved annual declaration is required before a yearly licence can be issued.'],
            ]);
        }

        return $this->issueInstitutionYearlyLicenceAction->execute(
            $declaration,
            $actor,
            $ipAddress,
            $userAgent
        );
    }
}
