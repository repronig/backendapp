<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\Institution;

class InstitutionRejectedMailable extends BaseAppMailable
{
    public function __construct(
        public Institution $institution,
        public ?string $reason = null,
    ) {}

    protected function subjectLine(): string
    {
        return 'Institution Registration Decision';
    }

    protected function viewName(): string
    {
        return 'emails.institutions.rejected';
    }

    protected function viewData(): array
    {
        return [
            'institution' => $this->institution,
            'reason' => $this->reason,
        ];
    }
}
