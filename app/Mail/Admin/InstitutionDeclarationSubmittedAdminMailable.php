<?php

namespace App\Mail\Admin;

use App\Mail\BaseAppMailable;
use App\Models\InstitutionAnnualDeclaration;

class InstitutionDeclarationSubmittedAdminMailable extends BaseAppMailable
{
    public function __construct(public InstitutionAnnualDeclaration $declaration) {}

    protected function subjectLine(): string
    {
        return 'Institution annual declaration submitted';
    }

    protected function viewName(): string
    {
        return 'emails.admin.institution-declaration-submitted';
    }

    protected function viewData(): array
    {
        $declaration = $this->declaration->fresh(['institution']);

        return [
            'declaration' => $declaration ?? $this->declaration,
            'institutionDisplayName' => ($declaration ?? $this->declaration)?->institution?->name
                ?: 'An institution',
            'licensingYearDisplay' => ($declaration ?? $this->declaration)?->licensing_year ?? '—',
            'adminDeclarationsUrl' => rtrim((string) config('app.frontend_url'), '/').'/admin/declarations',
        ];
    }
}
