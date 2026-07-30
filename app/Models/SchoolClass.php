<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory, UsesUuid;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'grade_level',
        'cohort',
        'school_year_id',
        'semester_id',
        'homeroom_teacher_id',
        'capacity',
        'status',
        'locked_at',
        'archived_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function studentAssignments()
    {
        return $this->hasMany(StudentClassAssignment::class, 'class_id');
    }

    public function assignments()
    {
        return $this->hasMany(TeachingAssignment::class, 'class_id');
    }

    public function fixedRoom()
    {
        return $this->hasOne(Room::class, 'fixed_class_id');
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_ACTIVE => 'Đang hoạt động',
            self::STATUS_LOCKED => 'Đã khóa',
            self::STATUS_ARCHIVED => 'Lưu trữ',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status ?: self::STATUS_DRAFT] ?? 'Bản nháp';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'bg-success',
            self::STATUS_LOCKED => 'bg-secondary',
            self::STATUS_ARCHIVED => 'bg-light text-muted border',
            default => 'bg-warning text-dark',
        };
    }

    public function isDraft(): bool
    {
        return ! $this->status || $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED || $this->archived_at !== null;
    }

    public function isReadOnly(): bool
    {
        return $this->isLocked() || $this->isArchived();
    }

    public function canEdit(): bool
    {
        return ! $this->isReadOnly();
    }

    public function canLock(): bool
    {
        return $this->isActive() || $this->isDraft();
    }

    public function canArchive(): bool
    {
        return ! $this->isArchived();
    }

    public function currentStudentCount(): int
    {
        return $this->relationLoaded('students')
            ? $this->students->count()
            : $this->students()->count();
    }

    public function maxCapacity(): int
    {
        return (int) ($this->capacity ?: 45);
    }
}
