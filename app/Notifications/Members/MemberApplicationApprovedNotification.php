<?php

namespace App\Notifications\Members;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberApplicationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ?string $memberCode = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function preferenceKey(): string
    {
        return 'member_application_approved';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('REPRONIG - Membership Approved')
            ->view('emails.members.application-approved', [
                'user' => $notifiable,
                'memberCode' => $this->memberCode,
            ]);
    }
}