<?php

namespace App\Mail\Works;

use App\Mail\BaseAppMailable;
use App\Models\Work;

class WorkReviewDecisionMemberMailable extends BaseAppMailable
{
    public function __construct(
        public Work $work,
        public string $decision,
    ) {}

    protected function subjectLine(): string
    {
        return match ($this->decision) {
            'verified' => 'Your work has been verified',
            'approved' => 'Your work has been approved',
            'rejected' => 'Your work was rejected',
            'changes_requested' => 'Changes requested for your work',
            default => 'Work review update',
        };
    }

    protected function viewName(): string
    {
        return 'emails.works.review-decision-member';
    }

    protected function viewData(): array
    {
        $work = $this->work->fresh(['member.user']);

        return [
            'work' => $work ?? $this->work,
            'decision' => $this->decision,
            'reviewNote' => ($work ?? $this->work)->review_reason ?? null,
            'memberWorksUrl' => rtrim((string) config('app.frontend_url'), '/').'/member/works',
        ];
    }
}
