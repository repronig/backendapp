<?php

namespace App\Mail\Works;

use App\Mail\BaseAppMailable;
use App\Models\Work;

class WorkUpdateRequestedMailable extends BaseAppMailable
{
    public function __construct(public Work $work) {}

    protected function subjectLine(): string
    {
        return 'Work Update Request Submitted';
    }

    protected function viewName(): string
    {
        return 'emails.works.update-requested';
    }

    protected function viewData(): array
    {
        return ['work' => $this->work];
    }
}
