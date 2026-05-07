<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\LicencePayment;

class PaymentInitiatedMailable extends BaseAppMailable
{
    public function __construct(public LicencePayment $payment) {}

    protected function subjectLine(): string
    {
        return 'Payment initiated';
    }

    protected function viewName(): string
    {
        return 'emails.institutions.payment-initiated';
    }

    protected function viewData(): array
    {
        $this->payment->loadMissing('licence');

        return [
            'payment' => $this->payment,
            'licence' => $this->payment->licence,
            'licencesUrl' => rtrim((string) config('app.frontend_url'), '/').'/institution/licences',
        ];
    }
}
