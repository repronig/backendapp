<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\Institution;
use App\Models\User;

class InstitutionWelcomeMailable extends BaseAppMailable
{
    public function __construct(
        public Institution $institution,
        public ?User $recipientUser = null
    ) {}

    protected function subjectLine(): string
    {
        return 'Welcome to REPRONIG';
    }

    protected function viewName(): string
    {
        return 'emails.institutions.welcome';
    }

    protected function viewData(): array
    {
        return [
            'institution' => $this->institution,
            'recipientName' => $this->recipientUser?->name,
            'platformUrl' => config('app.frontend_url'),
        ];
    }
}
