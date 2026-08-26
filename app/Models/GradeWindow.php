<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\SchoolYear;
use App\Models\Subject;

class GradeWindow extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

    protected $fillable = [
        'class_id',
        'subject_id',
        'semester_id',
        'school_year_id',
        'is_open',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('student_scores', 'grade_windows', 'score_record_type') ? 'student_scores' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('student_scores', 'grade_windows', 'score_record_type')) {
            static::addGlobalScope('grade_window_records', fn ($query) => $query->where('score_record_type', 'grade_window'));
            static::creating(fn (GradeWindow $window) => $window->score_record_type ??= 'grade_window');
        }
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
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
}
