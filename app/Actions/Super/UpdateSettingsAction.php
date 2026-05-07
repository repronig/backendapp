<?php

namespace App\Actions\Super;

use App\Actions\Audit\LogAuditAction;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateSettingsAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        array $data,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Collection {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): Collection {
            foreach ($data as $key => $value) {
                $before = Setting::query()
                    ->where('group', Setting::GROUP_GENERAL)
                    ->where('key', $key)
                    ->first()
                    ?->toArray();

                $setting = Setting::query()->updateOrCreate(
                    ['group' => Setting::GROUP_GENERAL, 'key' => $key],
                    ['value' => $value]
                );

                $fresh = $setting->fresh();

                $this->logAuditAction->execute(
                    $actor,
                    'platform_setting_updated',
                    $fresh,
                    $before,
                    $fresh->toArray(),
                    $ipAddress,
                    $userAgent
                );
            }

            return Setting::collectionKeyedByUniqueSettingKey();
        });
    }
}
