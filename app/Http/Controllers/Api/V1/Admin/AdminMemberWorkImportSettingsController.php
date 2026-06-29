<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Super\UpdateMembershipSettingsAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Support\MemberWorkImports\MemberWorkImportSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMemberWorkImportSettingsController extends BaseApiController
{
    public function show(MemberWorkImportSettings $settings): JsonResponse
    {
        return $this->success('Member work bulk import settings retrieved successfully.', [
            'member_work_bulk_import_enabled' => $settings->platformEnabled(),
            'member_work_bulk_import_tutorial_video_url' => $settings->tutorialVideoUrl(),
            'env_enabled' => $settings->envEnabled(),
            'effective_enabled' => $settings->enabled(),
        ]);
    }

    public function update(
        Request $request,
        UpdateMembershipSettingsAction $action,
        MemberWorkImportSettings $settings,
    ): JsonResponse {
        $validated = $request->validate([
            'member_work_bulk_import_enabled' => ['sometimes', 'boolean'],
            'member_work_bulk_import_tutorial_video_url' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
        ]);

        if ($validated === []) {
            return $this->error('Provide at least one bulk import setting to update.', 422);
        }

        if (
            ! $settings->envEnabled()
            && array_key_exists('member_work_bulk_import_enabled', $validated)
            && (bool) $validated['member_work_bulk_import_enabled']
        ) {
            return $this->error(
                'Member work bulk import is disabled by server configuration (MEMBER_WORK_IMPORT_ENABLED=false).',
                422
            );
        }

        $patch = [];
        if (array_key_exists('member_work_bulk_import_enabled', $validated)) {
            $patch['member_work_bulk_import_enabled'] = (bool) $validated['member_work_bulk_import_enabled'];
        }
        if (array_key_exists('member_work_bulk_import_tutorial_video_url', $validated)) {
            $url = trim((string) ($validated['member_work_bulk_import_tutorial_video_url'] ?? ''));
            $patch['member_work_bulk_import_tutorial_video_url'] = $url !== '' ? $url : null;
        }

        $action->execute(
            $patch,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        $settings = app(MemberWorkImportSettings::class);

        return $this->success('Member work bulk import settings updated successfully.', [
            'member_work_bulk_import_enabled' => $settings->platformEnabled(),
            'member_work_bulk_import_tutorial_video_url' => $settings->tutorialVideoUrl(),
            'env_enabled' => $settings->envEnabled(),
            'effective_enabled' => $settings->enabled(),
        ]);
    }
}
