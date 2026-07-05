<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory, UsesUuid;

    public const TYPE_REQUIRED = 'required';
    public const TYPE_ELECTIVE = 'elective';
    public const TYPE_REMEDIAL = 'remedial';

    public const TYPES = [
        self::TYPE_REQUIRED => 'Bắt buộc',
        self::TYPE_ELECTIVE => 'Tự chọn',
        self::TYPE_REMEDIAL => 'Phụ đạo',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Hoạt động',
        self::STATUS_INACTIVE => 'Ngưng áp dụng',
        self::STATUS_ARCHIVED => 'Lưu trữ',
    ];

    protected $fillable = [
        'code',
        'name',
        'credit',
        'type',
        'status',
    ];

    protected $casts = [
        'credit' => 'integer',
    ];

    public function assignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function timetableEntries()
    {
        return $this->hasMany(TimetableEntry::class);
    }

    public function scoreHeaders()
    {
        return $this->hasMany(ScoreHeader::class);
    }

    public function periodNorms()
    {
        return $this->hasMany(SubjectPeriodNorm::class);
    }

    public function periodNormForGrade(int $gradeLevel): ?SubjectPeriodNorm
    {
        if ($this->relationLoaded('periodNorms')) {
            return $this->periodNorms->firstWhere('grade_level', $gradeLevel);
        }

        return $this->periodNorms()->where('grade_level', $gradeLevel)->first();
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? self::TYPES[self::TYPE_REQUIRED];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::STATUS_ACTIVE];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_INACTIVE => 'bg-warning text-dark',
            self::STATUS_ARCHIVED => 'bg-secondary',
            default => 'bg-success',
        };
    }

    public function calculationWeight(): int
    {
        return max(1, (int) ($this->credit ?: 1));
    }

    public function isUsed(): bool
    {
        return $this->assignments()->exists()
            || $this->timetableEntries()->exists()
            || $this->scoreHeaders()->exists();
    }

    public function canEditCode(): bool
    {
        return ! $this->isUsed();
    }

    public function canDelete(): bool
    {
        return ! $this->isUsed();
    }
}
