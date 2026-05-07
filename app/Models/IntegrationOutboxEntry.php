<?php

namespace App\Models;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntegrationOutboxEntry extends Model
{
    use HasFactory;

    protected $table = 'integration_outbox';

    protected $fillable = [
        'provider',
        'subject_type',
        'subject_id',
        'operation',
        'payload',
        'status',
        'attempts',
        'last_error',
        'scheduled_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'provider' => IntegrationProvider::class,
            'status' => IntegrationSyncStatus::class,
            'payload' => 'array',
            'attempts' => 'integer',
            'scheduled_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
