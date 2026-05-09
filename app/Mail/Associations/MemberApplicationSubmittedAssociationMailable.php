<?php

namespace App\Mail\Associations;

use App\Models\MemberApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent synchronously when a member submits an application so delivery does not depend
 * on a mail queue worker (unlike {@see \App\Mail\BaseAppMailable} which implements ShouldQueue).
 */
class MemberApplicationSubmittedAssociationMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MemberApplication $memberApplication) {}

    protected function subjectLine(): string
    {
        $applicant = $this->memberApplication->user;
        $name = trim((string) ($applicant?->name ?? '')) !== ''
            ? (string) $applicant->name
            : (string) ($applicant?->email ?? 'Applicant');

        return 'Member Affiliation Request for '.$name;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine());
    }

    public function content(): Content
    {
        $application = $this->memberApplication->fresh(['user', 'association']);
        $base = rtrim((string) config('app.frontend_url'), '/');

        return new Content(
            view: 'emails.associations.member-application-submitted',
            with: [
                'memberApplication' => $application ?? $this->memberApplication,
                'verifyAffiliationUrl' => $base.'/association/applications',
            ],
        );
    }
}
