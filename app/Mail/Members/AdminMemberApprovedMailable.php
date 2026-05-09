<?php

namespace App\Mail\Members;

use App\Mail\BaseAppMailable;
use App\Models\MemberApplication;
use App\Models\User;

class AdminMemberApprovedMailable extends BaseAppMailable
{
    public function __construct(
        public MemberApplication $memberApplication,
        public User $reviewer,
        public string $decision = 'validated'
    ) {}

    protected function subjectLine(): string
    {
        return $this->decision === 'rejected'
            ? 'Member Affiliation Rejected by Association'
            : 'Member Affiliation Validated by Association';
    }

    protected function viewName(): string { return 'emails.members.admin-member-approved'; }
    protected function viewData(): array
    {
        return [
            'application' => $this->memberApplication,
            'reviewer' => $this->reviewer,
            'decision' => $this->decision,
        ];
    }
}
