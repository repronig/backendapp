<?php

namespace App\Models;

use App\Enums\AutomationTrigger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'trigger',
        'cron',
        'is_enabled',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'trigger' => AutomationTrigger::class,
            'is_enabled' => 'boolean',
            'config' => 'array',
        ];
    }
}
