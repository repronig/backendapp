<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\LicencePayment;
use App\Models\PaymentReconciliation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentReconciliation>
 */
class PaymentReconciliationFactory extends Factory
{
    protected $model = PaymentReconciliation::class;

    public function definition(): array
    {
        $status = fake()->randomElement([
            'matched',
            'partially_matched',
            'unmatched',
            'failed',
        ]);

        return [
            'licence_payment_id' => LicencePayment::factory(),
            'invoice_id' => fake()->boolean(80) ? Invoice::factory() : null,
            'processed_by_user_id' => fake()->boolean(80) ? User::factory() : null,
            'status' => $status,
            'reason_code' => match ($status) {
                'matched' => null,
                'partially_matched' => 'partial_allocation',
                'unmatched' => 'invoice_not_found',
                'failed' => 'processing_error',
            },
            'note' => fake()->sentence(),
            'before_json' => [
                'payment_status' => 'pending',
            ],
            'after_json' => [
                'payment_status' => $status === 'matched' ? 'paid' : 'pending',
            ],
            'processed_at' => fake()->dateTimeBetween('-10 days', 'now'),
        ];
    }
}