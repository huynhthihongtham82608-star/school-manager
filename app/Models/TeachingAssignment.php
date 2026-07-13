<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingAssignment extends Model
{
    use HasFactory, UsesUuid;

    public const ROLE_PRIMARY = 'primary';
    public const ROLE_REINFORCEMENT = 'reinforcement';
    public const ROLE_SUBSTITUTE = 'substitute';
    public const ROLE_OTHER = 'other';

    public const ROLES = [
        self::ROLE_PRIMARY => 'Giảng dạy chính',
        self::ROLE_REINFORCEMENT => 'Tăng cường / Bồi dưỡng',
        self::ROLE_SUBSTITUTE => 'Dạy thay tạm thời',
        self::ROLE_OTHER => 'Khác...',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Hoạt động',
        self::STATUS_PAUSED => 'Tạm dừng',
        self::STATUS_ARCHIVED => 'Lưu trữ',
    ];

    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject_id',
        'school_year_id',
        'semester_id',
        'role',
        'custom_role',
        'weekly_periods',
        'note',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'weekly_periods' => 'integer',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function roleLabel(): string
    {
        if ($this->role === self::ROLE_OTHER && trim((string) $this->custom_role) !== '') {
            return (string) $this->custom_role;
        }

        return self::ROLES[$this->role] ?? self::ROLES[self::ROLE_PRIMARY];
    }

    public function hasWeeklyPeriodOverride(): bool
    {
        return (int) ($this->weekly_periods ?: 0) > 0;
    }

    public function standardWeeklyPeriods(): ?int
    {
        $this->loadMissing(['classRoom', 'subject.periodNorms']);

        $gradeLevel = (int) ($this->classRoom?->grade_level ?: 0);
        if (! $gradeLevel || ! $this->subject) {
            return null;
        }

        return $this->subject->periodNormForGrade($gradeLevel)?->periods_per_week;
    }

    public function effectiveWeeklyPeriods(): ?int
    {
        if ($this->hasWeeklyPeriodOverride()) {
            return (int) $this->weekly_periods;
        }

        return $this->standardWeeklyPeriods();
    }

    public function weeklyPeriodSourceLabel(): string
    {
        return $this->hasWeeklyPeriodOverride() ? 'Đã điều chỉnh' : 'Theo định mức môn học';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::STATUS_ACTIVE];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAUSED => 'bg-warning text-dark',
            self::STATUS_ARCHIVED => 'bg-light text-muted border',
            default => 'bg-success',
        };
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED || $this->archived_at !== null;
    }
}
