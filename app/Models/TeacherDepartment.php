<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TeacherDepartment extends Model
{
    use HasFactory, UsesUuid;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Hoạt động',
        self::STATUS_INACTIVE => 'Ngưng sử dụng',
    ];

    protected $fillable = [
        'code',
        'name',
        'leader_teacher_id',
        'description',
        'status',
    ];

    protected static function booted(): void
    {
        if (Schema::hasColumn('teacher_departments', 'department_record_type')) {
            static::addGlobalScope('department_records', fn ($query) => $query->where('department_record_type', 'department'));
            static::creating(fn (TeacherDepartment $department) => $department->department_record_type ??= 'department');
        }
    }

    public function subjects()
    {
        $table = Schema::hasColumn('teacher_departments', 'department_record_type')
            && ! Schema::hasTable('teacher_department_subject') ? 'teacher_departments' : 'teacher_department_subject';

        $relation = $this->belongsToMany(Subject::class, $table, 'department_id', 'subject_id')
            ->withTimestamps();

        return $table === 'teacher_departments'
            ? $relation->wherePivot('department_record_type', 'subject')->withPivotValue('department_record_type', 'subject')
            : $relation;
    }

    public function leader()
    {
        return $this->belongsTo(Teacher::class, 'leader_teacher_id');
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class, 'department_id');
    }

    public function activeTeachers()
    {
        return $this->teachers()->where('work_status', Teacher::STATUS_WORKING);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::STATUS_ACTIVE];
    }

    public function statusBadgeClass(): string
    {
        return $this->status === self::STATUS_INACTIVE
            ? 'bg-secondary'
            : 'bg-success';
    }

    public function subjectNames(): string
    {
        $subjects = $this->relationLoaded('subjects')
            ? $this->subjects
            : $this->subjects()->orderBy('name')->get();

        return $subjects->pluck('name')->filter()->join(', ') ?: 'Chưa gán môn';
    }
}
