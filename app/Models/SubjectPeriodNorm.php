<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectPeriodNorm extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

    protected $fillable = [
        'subject_id',
        'grade_level',
        'periods_per_week',
    ];

    protected $casts = [
        'grade_level' => 'integer',
        'periods_per_week' => 'integer',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('subjects', 'subject_period_norms', 'subject_record_type') ? 'subjects' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('subjects', 'subject_period_norms', 'subject_record_type')) {
            static::addGlobalScope('subject_period_norm_records', fn ($query) => $query->where('subject_record_type', 'period_norm'));
            static::creating(fn (SubjectPeriodNorm $norm) => $norm->subject_record_type ??= 'period_norm');
        }
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
