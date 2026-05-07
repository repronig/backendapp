<?php

namespace App\Actions\Terms;

use App\Models\TermsAndCondition;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StoreTermsAndConditionAction
{
    public function execute(array $data, User $actor): TermsAndCondition
    {
        return DB::transaction(function () use ($data, $actor) {
            $isActive = (bool) ($data['is_active'] ?? false);
            $audience = (string) ($data['audience'] ?? 'all');

            if ($isActive) {
                TermsAndCondition::query()
                    ->where('audience', $audience)
                    ->update(['is_active' => false]);
            }

            return TermsAndCondition::query()->create([
                ...Arr::only($data, ['title', 'content', 'version', 'audience']),
                'is_active' => $isActive,
                'published_at' => $isActive ? now() : null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);
        });
    }
}
