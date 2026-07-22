<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory, UsesUuid;

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
        return $this->hasOne(User::class);
    }
}
