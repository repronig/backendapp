<?php

namespace App\Models;

use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'environment',
        'config',
        'is_enabled',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'provider' => IntegrationProvider::class,
            'environment' => IntegrationEnvironment::class,
            'config' => 'encrypted:array',
            'is_enabled' => 'boolean',
            'webhook_secret' => 'encrypted',
        ];
    }
}
