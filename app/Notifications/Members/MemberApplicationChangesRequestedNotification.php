<?php

namespace App\Notifications\Members;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberApplicationChangesRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $comment
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function preferenceKey(): string
    {
        return 'member_application_changes_requested';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Changes Requested on Your REPRONIG Membership Application')
            ->view('emails.members.application-changes-requested', [
                'user' => $notifiable,
                'comment' => $this->comment,
            ]);
    }
}