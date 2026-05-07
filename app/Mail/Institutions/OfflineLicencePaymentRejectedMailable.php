<?php

namespace App\Mail\Institutions;

use App\Mail\BaseAppMailable;
use App\Models\LicencePayment;

class OfflineLicencePaymentRejectedMailable extends BaseAppMailable
{
    public function __construct(
        public LicencePayment $payment,
        protected ?string $reason = null,
    ) {}

    protected function subjectLine(): string
    {
        return 'Offline Licence Payment Rejected';
    }

    protected function viewName(): string
    {
        return 'emails.institutions.offline-licence-payment-rejected';
    }

    protected function viewData(): array
    {
        $this->payment->loadMissing(['invoice', 'licence', 'declaration', 'institution']);

        return [
            'payment' => $this->payment,
            'reason' => $this->reason,
        ];
    }
}
