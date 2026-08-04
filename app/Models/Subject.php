<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory, UsesUuid;

    public const CODE_PREFIX = 'MH';

    public const TYPE_OFFICIAL = 'official';
    public const TYPE_HOMEROOM = 'homeroom';
    public const TYPE_ACTIVITY = 'activity';

    public const TYPE_REQUIRED = 'required';
    public const TYPE_ELECTIVE = 'elective';
    public const TYPE_REMEDIAL = 'remedial';

    public const TYPES = [
        self::TYPE_OFFICIAL => 'Chính khóa',
        self::TYPE_HOMEROOM => 'Chủ nhiệm',
        self::TYPE_ACTIVITY => 'Hoạt động',
    ];

    public const LEGACY_SCORABLE_TYPES = [
        self::TYPE_REQUIRED,
        self::TYPE_ELECTIVE,
        self::TYPE_REMEDIAL,
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Hoạt động',
        self::STATUS_INACTIVE => 'Ngưng áp dụng',
        self::STATUS_ARCHIVED => 'Lưu trữ',
    ];

    public const ASSESSMENT_GRADE_10 = 'GRADE_10';
    public const ASSESSMENT_ASSESSMENT = 'ASSESSMENT';
    public const ASSESSMENT_NONE = 'NONE';

    public const ASSESSMENT_NUMERIC = self::ASSESSMENT_GRADE_10;
    public const ASSESSMENT_PASS_FAIL = self::ASSESSMENT_ASSESSMENT;

    public const LEGACY_ASSESSMENT_NUMERIC = 'numeric';
    public const LEGACY_ASSESSMENT_PASS_FAIL = 'pass_fail';

    public const ASSESSMENT_TYPES = [
        self::ASSESSMENT_GRADE_10 => 'Thang điểm 10',
        self::ASSESSMENT_ASSESSMENT => 'Chọn đánh giá Đạt / Không đạt',
        self::ASSESSMENT_NONE => 'Không đánh giá',
    ];

    protected $fillable = [
        'code',
        'name',
        'credit',
        'type',
        'assessment_type',
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

    public function scoreColumns()
    {
        return $this->hasMany(ScoreColumn::class);
    }

    public function periodNorms()
    {
        return $this->hasMany(SubjectPeriodNorm::class);
    }

    public function gradeMappings()
    {
        return $this->hasMany(SubjectGradeMapping::class);
    }

    public function primaryTeachers()
    {
        return $this->hasMany(Teacher::class, 'primary_subject_id');
    }

    public function departments()
    {
        return $this->belongsToMany(TeacherDepartment::class, 'teacher_department_subject', 'subject_id', 'department_id')
            ->withTimestamps();
    }

    public function periodNormForGrade(int $gradeLevel): ?SubjectPeriodNorm
    {
        if ($this->relationLoaded('periodNorms')) {
            return $this->periodNorms->firstWhere('grade_level', $gradeLevel);
        }

        return $this->periodNorms()->where('grade_level', $gradeLevel)->first();
    }

    public function applicableGradeLevels(): array
    {
        $levels = $this->relationLoaded('gradeMappings')
            ? $this->gradeMappings->pluck('grade_level')
            : $this->gradeMappings()->pluck('grade_level');

        return $levels
            ->map(fn ($gradeLevel) => (int) $gradeLevel)
            ->filter(fn (int $gradeLevel) => in_array($gradeLevel, [10, 11, 12], true))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function appliesToGrade(int $gradeLevel): bool
    {
        if (! in_array($gradeLevel, [10, 11, 12], true)) {
            return false;
        }

        if ($this->relationLoaded('gradeMappings')) {
            return $this->gradeMappings->contains('grade_level', $gradeLevel);
        }

        return $this->gradeMappings()->where('grade_level', $gradeLevel)->exists();
    }

    public function typeLabel(): string
    {
        if (in_array($this->type, self::LEGACY_SCORABLE_TYPES, true)) {
            return self::TYPES[self::TYPE_OFFICIAL];
        }

        return self::TYPES[$this->type] ?? self::TYPES[self::TYPE_OFFICIAL];
    }

    public function isScorable(): bool
    {
        return $this->type === self::TYPE_OFFICIAL
            || in_array($this->type, self::LEGACY_SCORABLE_TYPES, true);
    }

    public function isEvaluated(): bool
    {
        return $this->isScorable() && ! $this->isNotEvaluated();
    }

    public function isOfficialSubject(): bool
    {
        return $this->isScorable();
    }

    public function isHomeroomSubject(): bool
    {
        return $this->type === self::TYPE_HOMEROOM;
    }

    public function isActivitySubject(): bool
    {
        return $this->type === self::TYPE_ACTIVITY;
    }

    public function requiresTeachingAssignment(): bool
    {
        return $this->isOfficialSubject();
    }

    public function requiresTeacherDepartment(): bool
    {
        return $this->isOfficialSubject();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::STATUS_ACTIVE];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function assessmentTypeLabel(): string
    {
        return self::ASSESSMENT_TYPES[$this->normalizedAssessmentType()] ?? self::ASSESSMENT_TYPES[self::ASSESSMENT_GRADE_10];
    }

    public function usesPassFailAssessment(): bool
    {
        return $this->normalizedAssessmentType() === self::ASSESSMENT_ASSESSMENT;
    }

    public function usesNumericAssessment(): bool
    {
        return $this->normalizedAssessmentType() === self::ASSESSMENT_GRADE_10;
    }

    public function isNotEvaluated(): bool
    {
        return $this->normalizedAssessmentType() === self::ASSESSMENT_NONE;
    }

    public function normalizedAssessmentType(): string
    {
        return self::normalizeAssessmentType($this->assessment_type);
    }

    public static function normalizeAssessmentType(?string $value): string
    {
        return match ($value) {
            self::ASSESSMENT_GRADE_10, self::LEGACY_ASSESSMENT_NUMERIC, null, '' => self::ASSESSMENT_GRADE_10,
            self::ASSESSMENT_ASSESSMENT, self::LEGACY_ASSESSMENT_PASS_FAIL => self::ASSESSMENT_ASSESSMENT,
            self::ASSESSMENT_NONE => self::ASSESSMENT_NONE,
            default => self::ASSESSMENT_GRADE_10,
        };
    }

    public static function evaluatedAssessmentValues(): array
    {
        return [
            self::ASSESSMENT_GRADE_10,
            self::ASSESSMENT_ASSESSMENT,
            self::LEGACY_ASSESSMENT_NUMERIC,
            self::LEGACY_ASSESSMENT_PASS_FAIL,
        ];
    }

    public function scopeWithEvaluatedAssessment($query)
    {
        return $query->where(function ($inner) {
            $inner->whereIn('assessment_type', self::evaluatedAssessmentValues())
                ->orWhereNull('assessment_type');
        });
    }

    public function scopeForGrade($query, int $gradeLevel)
    {
        return $query->whereHas('gradeMappings', fn ($mappingQuery) => $mappingQuery->where('grade_level', $gradeLevel));
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
            || $this->scoreHeaders()->exists()
            || $this->primaryTeachers()->exists()
            || $this->departments()->exists();
    }

    public function canEditCode(): bool
    {
        return ! $this->isUsed();
    }

    public function canDelete(): bool
    {
        return ! $this->isUsed();
    }

    public static function nextCode(): string
    {
        $maxNumber = static::query()
            ->where('code', 'like', self::CODE_PREFIX . '%')
            ->pluck('code')
            ->map(function ($code) {
                return preg_match('/^' . self::CODE_PREFIX . '(\d+)$/', (string) $code, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?? 0;

        return self::CODE_PREFIX . str_pad((string) ($maxNumber + 1), 3, '0', STR_PAD_LEFT);
    }
}
