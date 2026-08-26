<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentClassAssignment extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'status',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('student_movements', 'student_class_assignments', 'type') ? 'student_movements' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('student_movements', 'student_class_assignments', 'type')) {
            static::addGlobalScope('class_assignment_records', fn ($query) => $query->where('type', 'assignment'));
            static::creating(fn (StudentClassAssignment $assignment) => $assignment->type ??= 'assignment');
        }
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(SchoolYear::class, 'academic_year_id');
    }
}
