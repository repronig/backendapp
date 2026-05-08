<?php

namespace App\Actions\Media;

use App\Actions\Audit\LogAuditAction;
use App\Models\Association;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RemoveAssociationLogoAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        Association $association,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Association {
        return DB::transaction(function () use ($association, $actor, $ipAddress, $userAgent) {
            $before = $association->toArray();
            $disk = (string) config('media-library.disk_name', config('filesystems.default', 'local'));

            $association->clearMediaCollection('logo');
            if (filled($association->logo_path)) {
                Storage::disk($disk)->delete($association->logo_path);
                $association->forceFill(['logo_path' => null])->save();
            }

            $fresh = $association->fresh(['state', 'city']);

            $this->logAuditAction->execute(
                $actor,
                'association_logo_removed',
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
