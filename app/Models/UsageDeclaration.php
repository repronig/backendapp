<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'licence_id',
        'institution_id',
        'reporting_year',
        'declaration_status',
        'submitted_by_user_id',
        'declared_student_population',
        'declared_academic_staff_count',
        'declared_administrative_staff_count',
        'declared_campuses_count',
        'declared_library_capacity',
        'declaration_notes',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function licence(): BelongsTo
    {
        return $this->belongsTo(Licence::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}