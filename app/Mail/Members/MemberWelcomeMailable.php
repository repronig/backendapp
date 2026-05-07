<?php

namespace App\Mail\Members;

use App\Mail\BaseAppMailable;
use App\Models\User;

class MemberWelcomeMailable extends BaseAppMailable
{
    public function __construct(public User $memberUser) {}

    protected function subjectLine(): string
    {
        return 'Welcome to REPRONIG';
    }

    protected function viewName(): string
    {
        return 'emails.members.welcome';
    }

    protected function viewData(): array
    {
        return [
            'memberUser' => $this->memberUser->fresh(),
            'platformUrl' => config('app.frontend_url'),
        ];
    }
}
