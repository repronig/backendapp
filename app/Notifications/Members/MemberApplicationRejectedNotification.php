<?php

namespace App\Notifications\Members;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberApplicationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $reason
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function preferenceKey(): string
    {
        return 'member_application_rejected';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Update on Your REPRONIG Membership Application')
            ->view('emails.members.application-rejected', [
                'user' => $notifiable,
                'reason' => $this->reason,
            ]);
    }
}