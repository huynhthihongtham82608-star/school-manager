<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory, UsesUuid;

    public const STATUS_STUDYING = 'studying';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_GRADUATED = 'graduated';
    public const STATUS_DROPPED = 'dropped';
    public const STATUS_INACTIVE = 'inactive';
    public const GENDER_NAM = 'nam';
    public const GENDER_NU = 'nu';
    public const ADMISSION_NEW = 'new';
    public const ADMISSION_TRANSFER = 'transfer';

    protected $fillable = [
        'student_code',
        'name',
        'gender',
        'dob',
        'address',
        'place_of_birth',
        'ethnicity',
        'religion',
        'parent_phone',
        'email',
        'enrollment_date',
        'admission_type',
        'previous_school',
        'transfer_grade_level',
        'previous_class',
        'avatar',
        'note',
        'class_id',
        'school_year_id',
        'status',
    ];

    protected $casts = [
        'dob' => 'date',
        'enrollment_date' => 'date',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_STUDYING => 'Đang học',
            self::STATUS_RESERVED => 'Bảo lưu',
            self::STATUS_TRANSFERRED => 'Chuyển trường',
            self::STATUS_GRADUATED => 'Tốt nghiệp',
            self::STATUS_DROPPED => 'Nghỉ học',
        ];
    }

    public static function genderLabels(): array
    {
        return [
            self::GENDER_NAM => 'Nam',
            self::GENDER_NU => 'Nữ',
        ];
    }

    public static function admissionTypeLabels(): array
    {
        return [
            self::ADMISSION_NEW => 'Tuyển mới',
            self::ADMISSION_TRANSFER => 'Chuyển trường',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ($this->status === self::STATUS_INACTIVE ? 'Nghỉ học' : (string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_STUDYING => 'bg-success',
            self::STATUS_RESERVED => 'bg-warning text-dark',
            self::STATUS_TRANSFERRED => 'bg-info text-dark',
            self::STATUS_GRADUATED => 'bg-primary',
            self::STATUS_DROPPED, self::STATUS_INACTIVE => 'bg-secondary',
            default => 'bg-light text-muted border',
        };
    }

    public function genderLabel(): string
    {
        return self::genderLabels()[$this->gender] ?? '-';
    }

    public function admissionTypeLabel(): string
    {
        return self::admissionTypeLabels()[$this->admission_type] ?? '-';
    }

    public function cohortLabel(): string
    {
        if (! $this->enrollment_date) {
            return '-';
        }

        $gradeLevel = (int) ($this->classRoom?->grade_level ?: $this->transfer_grade_level ?: 10);
        $offset = match ($gradeLevel) {
            11 => 1,
            12 => 2,
            default => 0,
        };

        $startYear = ((int) $this->enrollment_date->format('Y')) - $offset;

        return $startYear . ' - ' . ($startYear + 3);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function classAssignments()
    {
        return $this->hasMany(StudentClassAssignment::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function scoreHeaders()
    {
        return $this->hasMany(ScoreHeader::class);
    }

    public function conductRecords()
    {
        return $this->hasMany(Conduct::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function parents()
    {
        return $this->belongsToMany(ParentProfile::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot(['relation']);
    }
}
