<?php

namespace App\Notifications\Licensing;

use App\Models\Licence;
use App\Models\LicencePayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicencePaymentInitiatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Licence $licence,
        protected LicencePayment $payment,
        protected ?string $paymentUrl = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function preferenceKey(): string
    {
        return 'licence_payment_initiated';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('REPRONIG Licence Payment Initiated')
            ->view('emails.licensing.payment-initiated', [
                'user' => $notifiable,
                'licence' => $this->licence,
                'payment' => $this->payment,
                'paymentUrl' => $this->paymentUrl,
            ]);
    }
}