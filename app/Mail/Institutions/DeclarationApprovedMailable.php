<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\InstitutionAnnualDeclaration;

class DeclarationApprovedMailable extends BaseAppMailable
{
    public function __construct(public InstitutionAnnualDeclaration $declaration) {}

    protected function subjectLine(): string
    {
        return 'Annual Declaration Approved';
    }

    protected function viewName(): string
    {
        return 'emails.institutions.declaration-approved';
    }

    protected function viewData(): array
    {
        return [
            'declaration' => $this->declaration,
            'invoiceUrl' => rtrim((string) config('app.frontend_url'), '/') . '/institution/invoices',
        ];
    }
}
