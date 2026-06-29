<?php

namespace App\Support\MemberWorkImports;

use App\Actions\Super\FormatSettingsPayloadAction;
use App\Actions\Super\GetSettingsAction;

class MemberWorkImportSettings
{
    public function __construct(
        protected GetSettingsAction $getSettingsAction,
        protected FormatSettingsPayloadAction $formatter,
    ) {}

    public function envEnabled(): bool
    {
        return (bool) config('member_work_imports.enabled', true);
    }

    public function platformEnabled(): bool
    {
        $settings = $this->formatter->execute($this->getSettingsAction->execute());
        $membership = $settings['membership'] ?? [];

        return ($membership['member_work_bulk_import_enabled'] ?? true) !== false;
    }

    public function enabled(): bool
    {
        return $this->envEnabled() && $this->platformEnabled();
    }

    public function tutorialVideoUrl(): ?string
    {
        $settings = $this->formatter->execute($this->getSettingsAction->execute());
        $membership = $settings['membership'] ?? [];
        $url = trim((string) ($membership['member_work_bulk_import_tutorial_video_url'] ?? ''));

        return $url !== '' ? $url : null;
    }
}
