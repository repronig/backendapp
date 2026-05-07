<?php

namespace App\Notifications\Licensing;

use App\Models\Licence;
use App\Models\LicencePayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicencePaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Licence $licence,
        protected LicencePayment $payment
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function preferenceKey(): string
    {
        return 'licence_payment_confirmed';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('REPRONIG Licence Payment Confirmed')
            ->view('emails.licensing.payment-confirmed', [
                'user' => $notifiable,
                'licence' => $this->licence,
                'payment' => $this->payment,
            ]);
    }
}