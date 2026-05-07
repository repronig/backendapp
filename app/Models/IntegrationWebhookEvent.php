<?php

namespace App\Models;

use App\Enums\IntegrationProvider;
use Illuminate\Database\Eloquent\Model;

class IntegrationWebhookEvent extends Model
{
    protected $table = 'integration_webhook_events';

    protected $fillable = [
        'provider',
        'idempotency_key',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'provider' => IntegrationProvider::class,
            'payload' => 'array',
        ];
    }
}
