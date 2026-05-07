<?php

namespace App\Actions\Terms;

use App\Models\TermsAndCondition;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateTermsAndConditionAction
{
    public function execute(TermsAndCondition $terms, array $data, User $actor): TermsAndCondition
    {
        return DB::transaction(function () use ($terms, $data, $actor) {
            $audience = (string) ($data['audience'] ?? $terms->audience);
            $isActivating = array_key_exists('is_active', $data) && (bool) $data['is_active'];

            if ($isActivating) {
                TermsAndCondition::query()
                    ->where('audience', $audience)
                    ->whereKeyNot($terms->id)
                    ->update(['is_active' => false]);
            }

            $terms->forceFill([
                ...Arr::only($data, ['title', 'content', 'version', 'audience', 'is_active']),
                'published_at' => $isActivating ? ($terms->published_at ?: now()) : ($data['published_at'] ?? $terms->published_at),
                'updated_by_user_id' => $actor->id,
            ])->save();

            return $terms->fresh();
        });
    }
}
