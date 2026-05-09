<?php

namespace App\Mail\Members;

use App\Mail\BaseAppMailable;
use App\Models\MemberApplication;

class MemberAffiliationAssociationDecisionMemberMailable extends BaseAppMailable
{
    public function __construct(
        public MemberApplication $memberApplication,
        public string $decision = 'validated',
    ) {}

    protected function subjectLine(): string
    {
        return $this->decision === 'rejected'
            ? 'Your membership affiliation was declined by your association'
            : 'Your membership affiliation was validated by your association';
    }

    protected function viewName(): string
    {
        return 'emails.members.member-affiliation-association-decision';
    }

    protected function viewData(): array
    {
        return [
            'subject' => $this->subjectLine(),
            'application' => $this->memberApplication,
            'decision' => $this->decision,
            'platformUrl' => config('app.frontend_url'),
        ];
    }
}
