<?php

namespace App\Mail\Works;

use App\Mail\BaseAppMailable;
use App\Models\Work;

class WorkUpdateRequestReviewedMailable extends BaseAppMailable
{
    public function __construct(
        public Work $work,
        public string $decision
    ) {}

    protected function subjectLine(): string
    {
        return $this->decision === 'approved'
            ? 'Work Update Request Approved'
            : 'Work Update Request Rejected';
    }

    protected function viewName(): string
    {
        return 'emails.works.update-request-reviewed';
    }

    protected function viewData(): array
    {
        return [
            'work' => $this->work,
            'decision' => $this->decision,
        ];
    }
}
