<?php

namespace App\Jobs;

use App\Enums\LicencePaymentStatus;
use App\Models\LicencePayment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupStalePendingPaymentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        LicencePayment::query()
            ->where('payment_status', LicencePaymentStatus::Pending->value)
            ->where('created_at', '<', now()->subHours(6))
            ->update(['payment_status' => LicencePaymentStatus::Cancelled->value]);
    }
}
