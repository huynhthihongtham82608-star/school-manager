<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use UsesUuid;

    public const SESSION_DAILY = 'daily';
    public const SESSION_PERIOD = 'period';

    public const SESSION_TYPES = [
        self::SESSION_DAILY => 'Theo ngày',
        self::SESSION_PERIOD => 'Theo tiết học',
    ];

    public const STATUSES = [
        'present' => 'Có mặt',
        'late' => 'Đi muộn',
        'excused' => 'Có phép',
        'absent' => 'Không phép',
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
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function sessionTypeLabel(): string
    {
        return self::SESSION_TYPES[$this->session_type ?: self::SESSION_DAILY] ?? self::SESSION_TYPES[self::SESSION_DAILY];
    }

    public function displaySessionLabel(): string
    {
        return $this->session_label ?: $this->sessionTypeLabel();
    }
}
