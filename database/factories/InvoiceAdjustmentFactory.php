<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceAdjustmentFactory extends Factory
{
    protected $model = InvoiceAdjustment::class;

    public function definition(): array
    {
        $adjustmentType = fake()->randomElement([
            'credit_note',
            'discount',
        ]);

        $reasonCode = match ($adjustmentType) {
            'credit_note' => fake()->randomElement([
                'duplicate_charge',
                'billing_error',
                'credit_correction',
            ]),
            'discount' => fake()->randomElement([
                'negotiated_discount',
                'promotional_discount',
                'goodwill_discount',
            ]),
        };

        return [
            'invoice_id' => Invoice::factory(),
            'created_by_user_id' => User::factory(),
            'adjustment_type' => $adjustmentType,
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'reason_code' => $reasonCode,
            'reason' => fake()->sentence(),
            'metadata_json' => [
                'source' => 'factory',
            ],
            'applied_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}