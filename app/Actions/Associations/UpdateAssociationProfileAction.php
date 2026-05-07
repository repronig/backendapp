<?php

namespace App\Actions\Associations;

use App\Actions\Audit\LogAuditAction;
use App\Models\Association;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateAssociationProfileAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        Association $association,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Association {
        return DB::transaction(function () use ($association, $data, $actor, $ipAddress, $userAgent) {
            $before = $association->toArray();

            $association->update([
                'name' => $data['name'] ?? $association->name,
                'type' => $data['type'] ?? $association->type,
                'description' => $data['description'] ?? $association->description,
                'contact_email' => $data['contact_email'] ?? $association->contact_email,
                'contact_phone' => $data['contact_phone'] ?? $association->contact_phone,
                'address_line_1' => $data['address_line_1'] ?? $association->address_line_1,
                'address_line_2' => $data['address_line_2'] ?? $association->address_line_2,
                'state_id' => array_key_exists('state_id', $data) ? $data['state_id'] : $association->state_id,
                'city_id' => array_key_exists('city_id', $data) ? $data['city_id'] : $association->city_id,
                'country' => $data['country'] ?? $association->country,
                'postal_code' => $data['postal_code'] ?? $association->postal_code,
            ]);

            $fresh = $association->fresh(['state', 'city']);

            $this->logAuditAction->execute(
                $actor,
                'association_profile_updated',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });
    }
}
