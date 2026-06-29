<?php

namespace App\Actions\Super;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateMembershipSettingsAction
{
    public function __construct(
        protected GetSettingsAction $getSettingsAction,
        protected FormatSettingsPayloadAction $formatter,
        protected UpdateSettingsAction $updateSettingsAction,
    ) {}

    /**
     * @param  array<string, mixed>  $membershipPatch
     */
    public function execute(
        array $membershipPatch,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Collection {
        return DB::transaction(function () use ($membershipPatch, $actor, $ipAddress, $userAgent): Collection {
            $current = $this->formatter->execute($this->getSettingsAction->execute());
            $membership = array_merge($current['membership'] ?? [], $membershipPatch);

            return $this->updateSettingsAction->execute(
                ['membership' => $membership],
                $actor,
                $ipAddress,
                $userAgent
            );
        });
    }
}
