<?php

namespace App\Mail\Members;

use App\Mail\BaseAppMailable;
use App\Models\MemberApplication;
use App\Models\User;

class AdminMemberApprovedMailable extends BaseAppMailable
{
    public function __construct(public MemberApplication $memberApplication, public User $reviewer) {}

    protected function subjectLine(): string { return 'New Member Approved by Association'; }
    protected function viewName(): string { return 'emails.members.admin-member-approved'; }
    protected function viewData(): array { return ['application' => $this->memberApplication, 'reviewer' => $this->reviewer]; }
}
