<?php

namespace App\Http\Controllers\Api\V1\Super;

use App\Actions\Super\FormatSettingsPayloadAction;
use App\Actions\Super\GetSettingsAction;
use App\Actions\Super\UpdateSettingsAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\UpdateSettingsRequest;
use Illuminate\Http\JsonResponse;

class SettingController extends BaseApiController
{
    public function index(
        GetSettingsAction $getSettingsAction,
        FormatSettingsPayloadAction $formatter
    ): JsonResponse {
        $settings = $getSettingsAction->execute();

        return $this->success(
            'Platform settings retrieved successfully.',
            $formatter->execute($settings)
        );
    }

    public function update(
        UpdateSettingsRequest $request,
        UpdateSettingsAction $action,
        FormatSettingsPayloadAction $formatter
    ): JsonResponse {
        $settings = $action->execute(
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Platform settings updated successfully.',
            $formatter->execute($settings)
        );
    }
}