<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Setting extends Model
{
    use HasFactory;

    /** Group used for super-updated payload sections (app, licensing, …). */
    public const GROUP_GENERAL = 'general';

    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Map setting values by `key` for {@see FormatSettingsPayloadAction}. When the same `key`
     * exists in more than one group, the `general` row wins so super-managed JSON is authoritative.
     *
     * @return Collection<string, mixed>
     */
    public static function collectionKeyedByUniqueSettingKey(): Collection
    {
        return static::query()
            ->get()
            ->groupBy('key')
            ->map(function (Collection $rows): mixed {
                if ($rows->count() === 1) {
                    return $rows->first()->value;
                }

                $general = $rows->firstWhere('group', self::GROUP_GENERAL);

                return ($general ?? $rows->first())->value;
            })
            ->sortKeys();
    }
}
