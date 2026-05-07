<?php

namespace App\Http\Controllers\Api\V1\Super;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreLanguageRequest;
use App\Http\Requests\Api\V1\UpdateLanguageRequest;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class LanguageController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $languages = Language::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success('Languages retrieved successfully.', $languages);
    }

    public function store(StoreLanguageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $language = Language::query()->create([
            ...$validated,
            'code' => strtolower($validated['code']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return $this->created('Language created successfully.', $language);
    }

    public function update(UpdateLanguageRequest $request, Language $language): JsonResponse
    {
        $validated = $request->validated();

        if (array_key_exists('code', $validated)) {
            $validated['code'] = strtolower($validated['code']);
        }

        $language->update($validated);

        return $this->success('Language updated successfully.', $language->fresh());
    }

    public function destroy(Language $language): JsonResponse
    {
        $language->update(['is_active' => false]);

        return $this->success('Language disabled successfully.', $language->fresh());
    }
}
