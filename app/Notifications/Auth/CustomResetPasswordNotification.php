<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $token
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function preferenceKey(): string
    {
        return 'password_reset';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = config('app.frontend_url')
            . '/reset-password?token=' . urlencode($this->token)
            . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset Your REPRONIG Password')
            ->view('emails.auth.reset-password', [
                'user' => $notifiable,
                'resetUrl' => $resetUrl,
                'expiryMinutes' => config('auth.passwords.users.expire', 60),
            ]);
    }
}