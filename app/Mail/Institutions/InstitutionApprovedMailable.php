<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\Institution;

class InstitutionApprovedMailable extends BaseAppMailable
{
    public function __construct(public Institution $institution) {}
    protected function subjectLine(): string { return 'Institution Approval Confirmation'; }
    protected function viewName(): string { return 'emails.institutions.approved'; }
    protected function viewData(): array { return ['institution' => $this->institution]; }
}
