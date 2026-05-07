<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_id',
        'file_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'uploaded_by_user_id',
    ];

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}