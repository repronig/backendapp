<?php

namespace App\Mail\Works;

use App\Mail\BaseAppMailable;
use App\Models\Work;

class WorkSubmittedAdminMailable extends BaseAppMailable
{
    public function __construct(public Work $work) {}

    protected function subjectLine(): string
    {
        return 'New work submitted for review';
    }

    protected function viewName(): string
    {
        return 'emails.works.submitted-admin';
    }

    protected function viewData(): array
    {
        $work = $this->work->fresh(['member.user']);
        $snapshot = $work ?? $this->work;
        $workStatus = $snapshot->work_status instanceof \BackedEnum ? $snapshot->work_status->value : (string) $snapshot->work_status;
        $verificationStatus = $snapshot->verification_status instanceof \BackedEnum ? $snapshot->verification_status->value : (string) $snapshot->verification_status;
        $memberName = optional(optional($snapshot->member)->user)->name;

        return [
            'work' => $snapshot,
            'memberName' => (string) ($memberName ?: 'A member'),
            'workTitle' => (string) ($snapshot->title ?: 'Untitled work'),
            'workReference' => (string) ($snapshot->reference_number ?: $snapshot->identifier_value ?: 'N/A'),
            'workStatus' => $workStatus,
            'verificationStatus' => $verificationStatus,
            'adminWorksUrl' => rtrim((string) config('app.frontend_url'), '/').'/admin/works',
        ];
    }
}
