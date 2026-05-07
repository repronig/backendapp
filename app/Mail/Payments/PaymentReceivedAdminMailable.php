<?php

namespace App\Mail\Payments;

use App\Mail\BaseAppMailable;
use App\Models\LicencePayment;

class PaymentReceivedAdminMailable extends BaseAppMailable
{
    public function __construct(public LicencePayment $payment) {}

    protected function subjectLine(): string
    {
        return 'Institution licence payment received';
    }

    protected function viewName(): string
    {
        return 'emails.payments.received-admin';
    }

    protected function viewData(): array
    {
        $payment = $this->payment->fresh(['institution', 'invoice', 'licence']);

        return [
            'payment' => $payment ?? $this->payment,
            'adminFinanceUrl' => rtrim((string) config('app.frontend_url'), '/').'/admin/finance',
        ];
    }
}
