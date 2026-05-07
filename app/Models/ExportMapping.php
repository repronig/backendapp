<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportMapping extends Model
{
    use HasFactory;

    protected $fillable = ['domain', 'mapping_key', 'mapping_json', 'is_active'];

    protected $casts = [
        'mapping_json' => 'array',
        'is_active' => 'boolean',
    ];
}
