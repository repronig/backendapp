<?php

namespace App\Notifications\Licensing;

use App\Models\Licence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UsageDeclarationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Licence $licence,
        protected ?string $declarationUrl = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function preferenceKey(): string
    {
        return 'usage_declaration_reminder';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: Submit Your REPRONIG Usage Declaration')
            ->view('emails.licensing.usage-declaration-reminder', [
                'user' => $notifiable,
                'licence' => $this->licence,
                'declarationUrl' => $this->declarationUrl,
            ]);
    }
}