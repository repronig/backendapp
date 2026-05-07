<?php

namespace App\Policies;

use App\Enums\LicencePaymentStatus;
use App\Models\LicencePayment;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverride;

class LicencePaymentPolicy
{
    use HandlesAdminOverride;

    public function view(User $user, LicencePayment $payment): bool
    {
        $payment->loadMissing('licence');

        return $payment->licence !== null
            && app(LicencePolicy::class)->view($user, $payment->licence);
    }

    public function downloadReceipt(User $user, LicencePayment $payment): bool
    {
        return $this->view($user, $payment)
            && $payment->payment_status === LicencePaymentStatus::Paid->value;
    }
}
