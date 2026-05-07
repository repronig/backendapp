<?php

namespace App\Actions\Super;

use App\Models\Setting;
use Illuminate\Support\Collection;

class GetSettingsAction
{
    public function execute(): Collection
    {
        return Setting::collectionKeyedByUniqueSettingKey();
    }
}
