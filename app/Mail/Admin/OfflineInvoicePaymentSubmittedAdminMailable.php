<?php

namespace App\Mail\Admin;

use App\Mail\BaseAppMailable;
use App\Models\LicencePayment;

class OfflineInvoicePaymentSubmittedAdminMailable extends BaseAppMailable
{
    public function __construct(public LicencePayment $payment) {}

    protected function subjectLine(): string
    {
        return 'Offline invoice payment submitted for review';
    }

    protected function viewName(): string
    {
        return 'emails.admin.offline-invoice-payment-submitted';
    }

    protected function viewData(): array
    {
        $payment = $this->payment->fresh(['institution', 'invoice']);

        return [
            'payment' => $payment ?? $this->payment,
            'adminFinanceUrl' => rtrim((string) config('app.frontend_url'), '/').'/admin/finance',
        ];
    }
}
