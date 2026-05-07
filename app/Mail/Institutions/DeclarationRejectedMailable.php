<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\InstitutionAnnualDeclaration;

class DeclarationRejectedMailable extends BaseAppMailable
{
    public function __construct(
        public InstitutionAnnualDeclaration $declaration,
        public ?string $reason = null
    ) {}

    protected function subjectLine(): string
    {
        return 'Annual Declaration Rejected';
    }

    protected function viewName(): string
    {
        return 'emails.institutions.declaration-rejected';
    }

    protected function viewData(): array
    {
        return [
            'declaration' => $this->declaration,
            'reason' => $this->reason,
            'declarationsUrl' => rtrim((string) config('app.frontend_url'), '/').'/institution/declarations',
        ];
    }
}
