<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

    public const STATUS_WORKING = 'working';
    public const STATUS_RESIGNED = 'resigned';
    public const GENDER_NAM = 'nam';
    public const GENDER_NU = 'nu';

    protected $fillable = [
        'teacher_code',
        'name',
        'dob',
        'gender',
        'phone',
        'email',
        'address',
        'joined_at',
        'work_status',
        'qualification',
        'main_subject',
        'primary_subject_id',
        'department_id',
        'is_homeroom',
    ];

    protected $casts = [
        'dob' => 'date',
        'joined_at' => 'date',
        'is_homeroom' => 'boolean',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('users', 'teachers', 'role_type') ? 'users' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('users', 'teachers', 'role_type')) {
            static::addGlobalScope('teacher_records', fn ($query) => $query->where('role_type', 'teacher'));
            static::creating(function (Teacher $teacher): void {
                $teacher->role_type ??= 'teacher';
                $teacher->role ??= 'teacher';
                $teacher->full_name ??= $teacher->name;
                $teacher->username ??= $teacher->teacher_code ?: $teacher->email;
                $teacher->password_hash ??= '$2y$12$Z2FJ9wVbk03D58EQ38Fn6O9z3.nBTeoNRZoh9c6uPfYRJrL46Y0wW';
                $teacher->teacher_id ??= $teacher->id;
            });
        }
    }

    public static function genderLabels(): array
    {
        return [
            self::GENDER_NAM => 'Nam',
            self::GENDER_NU => 'Nữ',
        ];
    }

    public static function workStatuses(): array
    {
        return [
            self::STATUS_WORKING => 'Đang công tác',
            self::STATUS_RESIGNED => 'Nghỉ việc',
        ];
    }

    public function genderLabel(): string
    {
        return self::genderLabels()[$this->gender] ?? '-';
    }

    public function workStatusLabel(): string
    {
        return self::workStatuses()[$this->work_status ?: self::STATUS_WORKING] ?? 'Đang công tác';
    }

    public function primarySubject()
    {
        return $this->belongsTo(Subject::class, 'primary_subject_id');
    }

    public function department()
    {
        return $this->belongsTo(TeacherDepartment::class, 'department_id');
    }

    public function leadingDepartment()
    {
        return $this->hasOne(TeacherDepartment::class, 'leader_teacher_id');
    }

    public function primarySubjectName(): string
    {
        return $this->primarySubject?->name ?: ($this->main_subject ?: '-');
    }

    public function workStatusBadgeClass(): string
    {
        return $this->work_status === self::STATUS_RESIGNED ? 'bg-secondary' : 'bg-success';
    }

    public function isWorking(): bool
    {
        return ($this->work_status ?: self::STATUS_WORKING) === self::STATUS_WORKING;
    }

    public function assignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function homeroomClasses()
    {
        return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'teacher_id', 'id');
    }
}
