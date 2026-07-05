<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectPeriodNorm extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'subject_id',
        'grade_level',
        'periods_per_week',
    ];

    protected $casts = [
        'grade_level' => 'integer',
        'periods_per_week' => 'integer',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
