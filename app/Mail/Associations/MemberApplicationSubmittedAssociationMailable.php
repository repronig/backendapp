<?php

namespace App\Mail\Associations;

use App\Mail\BaseAppMailable;
use App\Models\MemberApplication;

class MemberApplicationSubmittedAssociationMailable extends BaseAppMailable
{
    public function __construct(public MemberApplication $memberApplication) {}

    protected function subjectLine(): string
    {
        $applicant = $this->memberApplication->user;
        $name = trim((string) ($applicant?->name ?? '')) !== ''
            ? (string) $applicant->name
            : (string) ($applicant?->email ?? 'Applicant');

        return 'Member Affiliation Request for '.$name;
    }

    protected function viewName(): string
    {
        return 'emails.associations.member-application-submitted';
    }

    protected function viewData(): array
    {
        $application = $this->memberApplication->fresh(['user', 'association']);

        $base = rtrim((string) config('app.frontend_url'), '/');

        return [
            'memberApplication' => $application ?? $this->memberApplication,
            'verifyAffiliationUrl' => $base.'/association/applications',
        ];
    }
}
