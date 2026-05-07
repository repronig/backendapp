<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class LanguageController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return $this->success('Languages retrieved successfully.', $languages);
    }
}
