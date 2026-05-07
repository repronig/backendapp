<?php

namespace App\Actions\Media;

use App\Actions\Audit\LogAuditAction;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RemoveInstitutionLogoAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        Institution $institution,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Institution {
        return DB::transaction(function () use ($institution, $actor, $ipAddress, $userAgent) {
            $before = $institution->toArray();

            $institution->clearMediaCollection('logo');
            if (filled($institution->logo_path)) {
                Storage::disk('public')->delete($institution->logo_path);
                $institution->forceFill(['logo_path' => null])->save();
            }

            $fresh = $institution->fresh(['profile', 'state', 'city']);

            $this->logAuditAction->execute(
                $actor,
                'institution_logo_removed',
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
