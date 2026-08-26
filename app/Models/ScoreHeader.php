<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreHeader extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

    protected $fillable = [
        'student_id',
        'subject_id',
        'semester_id',
        'school_year_id',
        'average',
    ];

    protected $casts = [
        'average' => 'float',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('student_scores', 'score_headers', 'score_record_type') ? 'student_scores' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('student_scores', 'score_headers', 'score_record_type')) {
            static::addGlobalScope('score_header_records', fn ($query) => $query->where('score_record_type', 'header'));
            static::creating(function (ScoreHeader $header): void {
                $header->score_record_type ??= 'header';
                $header->score_type ??= 'average';
                $header->score_value ??= $header->average;
            });
        }
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function details()
    {
        return $this->hasMany(ScoreDetail::class);
    }
}
