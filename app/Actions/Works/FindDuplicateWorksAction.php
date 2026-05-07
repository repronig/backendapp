<?php

namespace App\Actions\Works;

use App\Models\Work;
use Illuminate\Database\Eloquent\Collection;

class FindDuplicateWorksAction
{
    public function execute(array $attributes, ?int $excludeWorkId = null): Collection
    {
        $fingerprint = Work::makeDuplicateFingerprint($attributes);

        if ($fingerprint === null) {
            return new Collection();
        }

        return Work::query()
            ->with(['member.user', 'member.association'])
            ->when($excludeWorkId, fn ($query) => $query->whereKeyNot($excludeWorkId))
            ->where('duplicate_fingerprint', $fingerprint)
            ->limit(10)
            ->get();
    }
}
