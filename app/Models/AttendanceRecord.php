<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use UsesUuid;

    public const STATUS_PRESENT = 'present';
    public const STATUS_LATE = 'late';
    public const STATUS_EXCUSED = 'excused';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_PERMITTED_ABSENT = 'permitted_absent';
    public const STATUS_UNEXCUSED_ABSENT = 'unexcused_absent';

    public const SESSION_DAILY = 'daily';
    public const SESSION_PERIOD = 'period';
    public const SESSION_MORNING = 'morning';
    public const SESSION_AFTERNOON = 'afternoon';

    public const SESSION_TYPES = [
        self::SESSION_MORNING => 'Buổi Sáng',
        self::SESSION_AFTERNOON => 'Buổi Chiều',
        self::SESSION_DAILY => 'Theo ngày',
        self::SESSION_PERIOD => 'Theo tiết học',
    ];

    public const STATUSES = [
        'present' => 'Có mặt',
        'late' => 'Đi muộn (M)',
        'excused' => 'Vắng có phép (P)',
        'absent' => 'Vắng không phép (X)',
    ];

    protected $fillable = [
        'student_id',
        'class_id',
        'semester_id',
        'attendance_date',
        'session_type',
        'timetable_entry_id',
        'session_label',
        'session_order',
        'session_key',
        'status',
        'note',
        'recorded_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function timetableEntry()
    {
        return $this->belongsTo(TimetableEntry::class, 'timetable_entry_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PRESENT => 'Có mặt',
            self::STATUS_LATE => 'Đi muộn (M)',
            self::STATUS_EXCUSED, self::STATUS_PERMITTED_ABSENT => 'Vắng có phép (P)',
            self::STATUS_ABSENT, self::STATUS_UNEXCUSED_ABSENT => 'Vắng không phép (K)',
            default => self::STATUSES[$this->status] ?? $this->status,
        };
    }

    public function sessionTypeLabel(): string
    {
        return self::SESSION_TYPES[$this->session_type ?: self::SESSION_DAILY] ?? self::SESSION_TYPES[self::SESSION_DAILY];
    }

    public function displaySessionLabel(): string
    {
        return $this->session_label ?: $this->sessionTypeLabel();
    }

    public function isPeriodSession(): bool
    {
        return $this->session_type === self::SESSION_PERIOD
            || str_starts_with((string) $this->session_key, 'period:');
    }

    public function isSuspiciousPeriodAbsence(): bool
    {
        return $this->isUnexcusedAbsent()
            && $this->isPeriodSession()
            && trim((string) $this->note) === '';
    }

    public function isPermittedAbsent(): bool
    {
        return in_array($this->status, [self::STATUS_EXCUSED, self::STATUS_PERMITTED_ABSENT], true);
    }

    public function isUnexcusedAbsent(): bool
    {
        return in_array($this->status, [self::STATUS_ABSENT, self::STATUS_UNEXCUSED_ABSENT], true);
    }
}
