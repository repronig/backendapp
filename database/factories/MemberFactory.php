<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        $approvalStatus = fake()->randomElement([
            'approved',
            'approved',
            'approved',
            'pending',
            'rejected',
        ]);

        $accountStatus = $approvalStatus === 'approved'
            ? fake()->randomElement([
                'active',
                'active',
                'active',
                'suspended',
            ])
            : 'inactive';

        return [
            'user_id' => User::factory()->create(['account_type' => 'member'])->id,
            'association_id' => Association::factory(),
            'member_code' => 'RM-'.strtoupper(fake()->bothify('##########??')),
            'member_type' => fake()->randomElement([
                'author',
                'publisher',
            ]),
            'member_provided_id' => fake()->optional(0.25)->bothify('REF-####??'),
            'external_id' => (string) Str::uuid(),
            'approval_status' => $approvalStatus,
            'account_status' => $accountStatus,
            'status_reason_code' => null,
            'status_reason' => null,
            'status_changed_by_user_id' => null,
            'status_changed_at' => null,
            'joined_at' => now(),
            'activated_at' => $approvalStatus === 'approved' ? now() : null,
        ];
    }
}
