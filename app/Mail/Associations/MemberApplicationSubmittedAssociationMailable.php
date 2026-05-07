<?php

namespace App\Mail\Associations;

use App\Mail\BaseAppMailable;
use App\Models\MemberApplication;

class MemberApplicationSubmittedAssociationMailable extends BaseAppMailable
{
    public function __construct(public MemberApplication $memberApplication) {}

    protected function subjectLine(): string
    {
        return 'New Member Application Submitted';
    }

    protected function viewName(): string
    {
        return 'emails.associations.member-application-submitted';
    }

    protected function viewData(): array
    {
        $application = $this->memberApplication->fresh(['user', 'association']);

        return [
            'memberApplication' => $application ?? $this->memberApplication,
            'adminMembershipUrl' => rtrim((string) config('app.frontend_url'), '/').'/admin/membership',
        ];
    }
}
