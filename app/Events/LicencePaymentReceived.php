<?php

namespace App\Events;

use App\Models\LicencePayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicencePaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public LicencePayment $payment) {}
}
